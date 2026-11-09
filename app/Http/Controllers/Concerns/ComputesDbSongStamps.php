<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DbAlbum;
use App\Models\DbConcert;
use App\Models\DbSingle;
use App\Models\DbSetlist;
use App\Models\DbSong;
use App\Models\SlSetlist;
use App\Models\SlSong;
use App\Models\UserConcert;
use App\Models\UserSetlist;
use App\Models\UserSong;

trait ComputesDbSongStamps
{
    // ソロプロジェクトとバンドのセットリストには、お互いの曲が含まれることがあるため（DbSetlistResource参照）、
    // 集計時は組になっているアーティストのツアーのセットリストも走査対象に含める。
    // 曲自体の帰属（db_songs.artist_id）で最終的に絞り込むので、バンド側の集計に
    // ソロプロジェクトの曲が混ざったり、その逆が起きたりはしない。
    private function crossoverTourArtistIds(int $artistId): array
    {
        return \App\Models\Artist::songArtistIds($artistId);
    }

    // 公式ツアー記録（db_setlists）上、一度でも演奏された曲IDを集める。
    // ここに含まれない曲は「ライブでそもそも未演奏」として台紙自体をグレー表示する。
    // Yuki本人の記録（StatsController）・外部ユーザーの記録（MyPageStatsController）双方の
    // スタンプ帳で共通して使う、そのアーティスト単位の計算。
    // 「全セットリスト登録済」に印が無い（登録の途中かもしれない）、ライブが1件も無い、またはどのライブのセトリも登録していないときは、未演奏かどうか判断できないので null
    private function everPerformedDbSongIds(int $artistId): ?array
    {
        if (!\App\Models\Artist::whereKey($artistId)->value('setlists_complete')) {
            return null;
        }
        $songArtistIds = DbSong::pluck('artist_id', 'id');
        $tourIds = DbConcert::whereIn('artist_id', $this->crossoverTourArtistIds($artistId))->pluck('id');
        $tourSetlists = DbSetlist::whereIn('tour_id', $tourIds)->get();
        if ($tourSetlists->isEmpty()) {
            return null;
        }

        $everPerformedDbSongIds = [];
        foreach ($tourSetlists as $setlist) {
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song']) && ($songArtistIds[(int)$s['song']] ?? null) === $artistId) {
                    $everPerformedDbSongIds[(int)$s['song']] = true;
                }
            }
        }

        return $everPerformedDbSongIds;
    }

    // スタンプの並べ替え「Last Performed」「Most Performed」用。曲ごとに、アーティストが最後にライブで演奏した日と、演奏したライブの数
    // （[曲の id => ['performed_last' => 「2026-10-31」, 'performed_count' => 12]]）。
    // 日付はそのライブの終了日（無ければ開始日。まだ終わっていないツアーは今日）。回数はツアー・ライブの数（同じツアーの公演は1回）。
    // これから始まるライブは数えない。$userArtist = true のときは、マイページで作ったアーティストのライブ（user_concerts / user_setlists）で見る
    private function performedStats(int $artistId, bool $userArtist = false): array
    {
        $today = now()->toDateString();
        $tours = $userArtist
            ? UserConcert::where('user_artist_id', $artistId)
            : DbConcert::whereIn('artist_id', $this->crossoverTourArtistIds($artistId));
        $tourDates = $tours->whereNotNull('date1')->whereDate('date1', '<=', $today)->get(['id', 'date1', 'date2'])
            ->mapWithKeys(fn ($tour) => [$tour->id => min(substr((string) ($tour->date2 ?: $tour->date1), 0, 10), $today)]);
        $songIds = ($userArtist ? UserSong::where('user_artist_id', $artistId) : DbSong::where('artist_id', $artistId))->pluck('id')->flip();
        $setlists = $userArtist
            ? UserSetlist::whereIn('user_concert_id', $tourDates->keys())->get()
            : DbSetlist::whereIn('tour_id', $tourDates->keys())->get();

        $last = [];
        $lastTitle = [];
        $lastPatternDate = [];
        $tours = [];
        $titleTours = [];
        $performedTitles = [];
        foreach ($setlists->sortBy('id') as $setlist) {
            $tourId = $userArtist ? $setlist->user_concert_id : $setlist->tour_id;
            $date = $tourDates[$tourId] ?? null;
            // 同じツアーのパターンどうしは、最後の公演日（schedule_dates）が遅いほうの表記にする
            $patternDate = (string) collect($setlist->schedule_dates ?? [])->max();
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                $songId = is_numeric($s['song'] ?? null) ? (int) $s['song'] : null;
                if (!$date || $songId === null || !isset($songIds[$songId])) {
                    continue;
                }
                // そのとき演奏した表記（セトリの alternative_title。無ければ曲名で ''）
                $title = trim((string) ($s['alternative_title'] ?? ''));
                $tours[$songId][$tourId] = true;
                $titleTours[$songId][$title][$tourId] = true;
                if ($title !== '') {
                    $performedTitles[$songId][$title] = true;
                }
                if (($last[$songId] ?? '') < $date || (($last[$songId] ?? '') === $date && $lastPatternDate[$songId] <= $patternDate)) {
                    $last[$songId] = $date;
                    $lastTitle[$songId] = $title;
                    $lastPatternDate[$songId] = $patternDate;
                }
            }
        }

        $stats = [];
        foreach ($last as $songId => $date) {
            $stats[$songId] = [
                'performed_last' => $date,
                'performed_count' => count($tours[$songId]),
                // Last Performed で並べたときに出す、最後に演奏したときの表記（'' は曲名のまま）
                'performed_last_title' => $lastTitle[$songId],
                // Most Performed で並べたときに出す、いちばん多くのツアーで演奏した表記
                'performed_most_title' => \App\Support\StampHeardOrder::mostTitle($titleTours[$songId], $lastTitle[$songId]),
                // ライブで演奏した別表記（長押しのバージョンの選択肢に出す）
                'performed_titles' => array_keys($performedTitles[$songId] ?? []),
            ];
        }

        return $stats;
    }

    // ユーザー登録アーティストのmanage画面に登録された全ツアー記録（user_setlists）上、
    // 一度でも演奏された曲IDを集める。ここに含まれない曲は「ライブでそもそも未演奏」
    // として台紙自体をグレー表示する。公式側のeverPerformedDbSongIdsと同じ考え方だが、
    // 演奏記録の裏付けがYuki本人の公式記録ではなく、そのアーティストの作成者（または
    // 誰か）がmanage画面で自己申告したものである点が異なる。
    // ツアーの画面で「全セットリスト登録済」に印が無い（登録の途中かもしれない）か、セトリが1件も無ければ、判断できないので null
    private function everPerformedUserSongIds(int $userArtistId): ?array
    {
        if (!\App\Models\UserArtist::whereKey($userArtistId)->value('setlists_complete')) {
            return null;
        }
        $songArtistIds = UserSong::where('user_artist_id', $userArtistId)->pluck('user_artist_id', 'id');
        $concertIds = UserConcert::where('user_artist_id', $userArtistId)->pluck('id');
        $setlists = UserSetlist::whereIn('user_concert_id', $concertIds)->get();
        if ($setlists->isEmpty()) {
            return null;
        }

        $everPerformedUserSongIds = [];
        foreach ($setlists as $setlist) {
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song']) && isset($songArtistIds[(int) $s['song']])) {
                    $everPerformedUserSongIds[(int) $s['song']] = true;
                }
            }
        }

        return $everPerformedUserSongIds;
    }

    // スタンプ帳の絞り込み用。シングルは表題曲（両A面は「A / B」の曲数ぶん先頭から、
    // EPは全曲）、アルバムはベスト盤も含めtracklistの収録曲すべてを対象にする。
    // exceptionは表記違いの表示名にも使われるため、曲IDがあれば収録曲として数える。
    // 絞り込み時は、スタンプの曲名をそのシングル・アルバム側の表記（exception）に差し替える。
    // $userArtist = true のときは、マイページで作ったアーティストのシングル・アルバム（user_singles / user_albums）で作る
    private function stampDiscographyFilters(int $artistId, bool $userArtist = false): array
    {
        $singleModel = $userArtist ? \App\Models\UserSingle::class : DbSingle::class;
        $albumModel = $userArtist ? \App\Models\UserAlbum::class : DbAlbum::class;
        $artistColumn = $userArtist ? 'user_artist_id' : 'artist_id';

        $songTracks = fn ($tracklist) => collect($tracklist ?? [])
            ->filter(fn ($track) => is_numeric($track['id'] ?? null));

        $singleTitleTracks = $singleModel::where($artistColumn, $artistId)->orderBy('date')->orderBy('id')->get()
            ->flatMap(function ($single) use ($songTracks) {
                $tracklist = $single->tracklist ?? [];
                if (!$single->ep) {
                    $tracklist = array_slice($tracklist, 0, count(preg_split('/[\/／]/u', $single->title ?? '')));
                }
                return $songTracks($tracklist)->values();
            })
            ->unique(fn ($track) => (int) $track['id']);

        $albums = $albumModel::where($artistColumn, $artistId)->orderBy('date')->orderBy('id')->get()
            ->map(fn ($album) => [
                'key' => 'album-' . $album->id,
                'title' => $album->title,
                // 曲ID => 曲順（同じ曲が複数回入る場合は最初の位置）
                'song_ids' => $songTracks($album->tracklist)
                    ->map(fn ($track) => (int) $track['id'])
                    ->unique()
                    ->values()
                    ->flip()
                    ->map(fn ($index) => $index + 1)
                    ->all(),
                // 同じ曲が複数回入る場合（Radio Mix等）は最初のトラックの表記を使う
                'track_titles' => $songTracks($album->tracklist)
                    ->unique(fn ($track) => (int) $track['id'])
                    ->filter(fn ($track) => filled($track['exception'] ?? null))
                    ->mapWithKeys(fn ($track) => [(int) $track['id'] => $track['exception']])
                    ->all(),
            ])
            ->filter(fn ($album) => !empty($album['song_ids']))
            ->values()
            ->all();

        // 曲ID => ['single-{id}' => 表記]。シングルの全曲（カップリングも）の別表記。長押しのバージョンの選択肢に出す
        $singleVersions = [];
        foreach ($singleModel::where($artistColumn, $artistId)->orderBy('date')->orderBy('id')->get(['id', 'tracklist']) as $single) {
            foreach ($songTracks($single->tracklist) as $track) {
                if (filled($track['exception'] ?? null)) {
                    $singleVersions[(int) $track['id']]['single-' . $single->id] ??= $track['exception'];
                }
            }
        }

        return [
            'single_versions' => $singleVersions,
            // 曲ID => シングル発売順（同じ曲が複数のシングルに入る場合は最初のシングル）
            'single_song_ids' => $singleTitleTracks->values()->mapWithKeys(fn ($track, $index) => [(int) $track['id'] => $index + 1])->all(),
            'single_titles' => $singleTitleTracks
                ->filter(fn ($track) => filled($track['exception'] ?? null))
                ->mapWithKeys(fn ($track) => [(int) $track['id'] => $track['exception']])
                ->all(),
            'albums' => $albums,
        ];
    }

    private function stampFilterKeys(int $songId, array $filters): array
    {
        $keys = isset($filters['single_song_ids'][$songId]) ? ['single'] : [];
        foreach ($filters['albums'] as $album) {
            if (isset($album['song_ids'][$songId])) {
                $keys[] = $album['key'];
            }
        }

        return $keys;
    }

    private function stampTrackOrders(int $songId, array $filters): array
    {
        $orders = isset($filters['single_song_ids'][$songId]) ? ['single' => $filters['single_song_ids'][$songId]] : [];
        foreach ($filters['albums'] as $album) {
            if (isset($album['song_ids'][$songId])) {
                $orders[$album['key']] = $album['song_ids'][$songId];
            }
        }

        return $orders;
    }

    // 長押しで選べるバージョン（[title_key => 表記]）。シングル・アルバムの表記（カップリングも）と、ライブで演奏した別表記。
    // ライブの表記は文字が長いことがあるので、表記から作った印（live-…）を title_key にする
    private function stampVersions(int $songId, array $filters, array $performedTitles): array
    {
        $versions = $this->stampTrackTitles($songId, $filters) + ($filters['single_versions'][$songId] ?? []);
        foreach ($performedTitles as $title) {
            $versions[\App\Support\StampDeco::liveTitleKey($title)] = $title;
        }

        return $versions;
    }

    private function stampTrackTitles(int $songId, array $filters): array
    {
        $titles = isset($filters['single_titles'][$songId]) ? ['single' => $filters['single_titles'][$songId]] : [];
        foreach ($filters['albums'] as $album) {
            if (isset($album['track_titles'][$songId])) {
                $titles[$album['key']] = $album['track_titles'][$songId];
            }
        }

        return $titles;
    }

    // Yuki本人が実際にライブで演奏した記録（SlSetlist、フェスのゲスト出演含む）がある
    // db_song_idの集合を、アーティストIDに関わらず一度に集める。
    // Live Stamp Bookの「アーティストごとのUnique Songs」集計で、全アーティストを
    // 都度SlSetlist全件走査するのを避けるための共通ヘルパー。
    private function playedDbSongIdsByArtist(): array
    {
        $today = now()->toDateString();
        $playedSlSongIds = [];
        $setlists = SlSetlist::where('date', '<=', $today)->get();

        foreach ($setlists as $setlist) {
            $allSongs = array_merge(
                $setlist->setlist ?? [],
                $setlist->encore ?? [],
                $this->flattenFesSongs($setlist->fes_setlist ?? []),
                $this->flattenFesSongs($setlist->fes_encore ?? [])
            );

            foreach ($allSongs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    $playedSlSongIds[(int)$songData['song']] = true;
                }
            }
        }

        $playedDbSongIdsByArtist = [];
        SlSong::whereNotNull('db_song_id')->select('id', 'artist_id', 'db_song_id')
            ->get()
            ->each(function (SlSong $slSong) use ($playedSlSongIds, &$playedDbSongIdsByArtist) {
                if (isset($playedSlSongIds[$slSong->id])) {
                    $playedDbSongIdsByArtist[$slSong->artist_id][(int)$slSong->db_song_id] = true;
                }
            });

        return $playedDbSongIdsByArtist;
    }
}

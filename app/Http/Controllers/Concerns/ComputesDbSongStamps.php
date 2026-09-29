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
    // B'zのセットリストには稲葉浩志のソロ曲が含まれることがあるため（DbSetlistResource参照）、
    // 稲葉浩志の集計時はB'zのツアーのセットリストも走査対象に含める。
    // 曲自体の帰属（db_songs.artist_id）で最終的に絞り込むので、B'z側の集計に
    // 稲葉浩志の曲が混ざったり、その逆が起きたりはしない。
    private const BZ_ARTIST_ID = 3;
    private const INABA_ARTIST_ID = 39;

    private function crossoverTourArtistIds(int $artistId): array
    {
        if ($artistId === self::INABA_ARTIST_ID) {
            return [self::INABA_ARTIST_ID, self::BZ_ARTIST_ID];
        }

        return [$artistId];
    }

    // 公式ツアー記録（db_setlists）上、一度でも演奏された曲IDを集める。
    // ここに含まれない曲は「ライブでそもそも未演奏」として台紙自体をグレー表示する。
    // Yuki本人の記録（StatsController）・外部ユーザーの記録（MyPageStatsController）双方の
    // スタンプ帳で共通して使う、そのアーティスト単位の計算。
    private function everPerformedDbSongIds(int $artistId): array
    {
        $songArtistIds = DbSong::pluck('artist_id', 'id');
        $tourIds = DbConcert::whereIn('artist_id', $this->crossoverTourArtistIds($artistId))->pluck('id');
        $tourSetlists = DbSetlist::whereIn('tour_id', $tourIds)->get();

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

    // ユーザー登録アーティストのmanage画面に登録された全ツアー記録（user_setlists）上、
    // 一度でも演奏された曲IDを集める。ここに含まれない曲は「ライブでそもそも未演奏」
    // として台紙自体をグレー表示する。公式側のeverPerformedDbSongIdsと同じ考え方だが、
    // 演奏記録の裏付けがYuki本人の公式記録ではなく、そのアーティストの作成者（または
    // 誰か）がmanage画面で自己申告したものである点が異なる。
    private function everPerformedUserSongIds(int $userArtistId): array
    {
        $songArtistIds = UserSong::where('user_artist_id', $userArtistId)->pluck('user_artist_id', 'id');
        $concertIds = UserConcert::where('user_artist_id', $userArtistId)->pluck('id');
        $setlists = UserSetlist::whereIn('user_concert_id', $concertIds)->get();

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
    private function stampDiscographyFilters(int $artistId): array
    {
        $songTracks = fn ($tracklist) => collect($tracklist ?? [])
            ->filter(fn ($track) => is_numeric($track['id'] ?? null));

        $singleTitleTracks = DbSingle::where('artist_id', $artistId)->orderBy('date')->orderBy('id')->get()
            ->flatMap(function (DbSingle $single) use ($songTracks) {
                $tracklist = $single->tracklist ?? [];
                if (!$single->ep) {
                    $tracklist = array_slice($tracklist, 0, count(preg_split('/[\/／]/u', $single->title ?? '')));
                }
                return $songTracks($tracklist)->values();
            })
            ->unique(fn ($track) => (int) $track['id']);

        $albums = DbAlbum::where('artist_id', $artistId)->orderBy('date')->orderBy('id')->get()
            ->map(fn (DbAlbum $album) => [
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

        return [
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

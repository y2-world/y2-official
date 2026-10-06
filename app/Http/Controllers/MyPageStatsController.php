<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ComputesDbSongStamps;
use App\Http\Controllers\Concerns\SplitsKindRef;
use App\Models\Artist;
use App\Models\DbSetlist;
use App\Models\DbSong;
use App\Models\ExternalUser;
use App\Models\UserArtist;
use App\Models\UserSetlist;
use App\Models\UserSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyPageStatsController extends Controller
{
    use ComputesDbSongStamps;
    use SplitsKindRef;

    // My Stamp Books一覧（アーティストごとのスタンプ帳への入り口）。
    // 対象は自分が出席記録を残しているアーティスト（公式・ユーザー登録の両方）のみ。
    public function index()
    {
        $attendances = Auth::guard('external')->user()
            ->attendances()
            ->withSetlist()
            ->with(['dbSetlist.tour', 'userSetlist.concert'])
            ->get();

        $attendedOfficialArtistIds = $attendances
            ->pluck('dbSetlist.tour.artist_id')
            ->filter()
            ->unique();
        $attendedUserArtistIds = $attendances
            ->pluck('userSetlist.concert.user_artist_id')
            ->filter()
            ->unique();

        // 曲が登録されていないアーティストはスタンプ帳が作れないので出さない。
        // 公式・ユーザー登録をまとめて、初めてライブに行った順に並べる（My Statistics のスタンプのタブと同じ）
        $firstDates = \App\Models\ExternalUserAttendance::firstDatesByArtistRef($attendances);
        $stampBookArtists = Artist::whereIn('id', $attendedOfficialArtistIds)->whereHas('songs')->get()
            ->map(fn ($artist) => ['ref' => 'official-' . $artist->id, 'name' => $artist->name])
            ->concat(UserArtist::whereIn('id', $attendedUserArtistIds)
                ->whereIn('id', \App\Models\UserSong::select('user_artist_id'))->get()
                ->map(fn ($artist) => ['ref' => 'user-' . $artist->id, 'name' => $artist->name]))
            ->sortBy(fn ($artist) => $firstDates[$artist['ref']] ?? '9999-12-31')
            ->values();

        return view('mypage.stats.stamps_index', compact('stampBookArtists'));
    }

    // アーティスト別の自分専用統計（/stats/artist/{id} のMy Page版）。
    // $artistId は "official-{id}" / "user-{id}" 形式。
    // 統計を見るユーザー。?user= で他のユーザーを指定すると、その人のアーティスト別のstats・スタンプ帳を見られる
    //（プロフィールと同じく、ログインしている人なら誰でも見られる）
    private ?ExternalUser $statsUser = null;

    private function resolveStatsUser(Request $request): ExternalUser
    {
        $viewer = Auth::guard('external')->user();
        $viewedUserId = $request->query('user');
        if (!$viewedUserId || (int) $viewedUserId === (int) $viewer->id) {
            return $viewer;
        }

        return ExternalUser::findOrFail($viewedUserId);
    }

    // タブの「Artists」：統計を見ているユーザーが参加したアーティスト（公式・自分で登録したもの）に切り替える
    private function tabArtists(string $currentRef): array
    {
        $attendances = $this->statsUser->attendances()->withSetlist()->with(['dbSetlist.tour.artist', 'userSetlist.concert.artist'])->get();
        $artists = [];
        foreach ($attendances as $attendance) {
            if ($artist = $attendance->dbSetlist?->tour?->artist) {
                $artists['official-' . $artist->id] ??= ['name' => $artist->name, 'count' => 0];
                $artists['official-' . $artist->id]['count']++;
            } elseif ($artist = $attendance->userSetlist?->concert?->artist) {
                $artists['user-' . $artist->id] ??= ['name' => $artist->name, 'count' => 0];
                $artists['user-' . $artist->id]['count']++;
            }
        }
        uasort($artists, fn ($a, $b) => $b['count'] <=> $a['count']);

        return collect($artists)->map(fn ($a, $ref) => [
            'name' => $a['name'],
            'url' => route('mypage.stats.artist', $this->isOwner() ? $ref : ['artistId' => $ref, 'user' => $this->statsUser->id]),
            'current' => $ref === $currentRef,
        ])->values()->all();
    }

    private function isOwner(): bool
    {
        return (int) $this->statsUser->id === (int) Auth::guard('external')->id();
    }

    public function artist(Request $request, $artistId)
    {
        [$kind, $id] = $this->splitRef($artistId);
        $this->statsUser = $this->resolveStatsUser($request);

        if ($kind === 'official') {
            return $this->officialArtistStats($id);
        }

        return $this->userArtistStats($id);
    }

    private function officialArtistStats($id)
    {
        $artist = Artist::find($id);
        if (!$artist) {
            abort(404);
        }

        // type=4（ソロ）は本人単独のプロジェクトであり、アーティスト本体の統計には含めない
        // （イベント・ap bank fesはそのアーティスト自身としての出演なので含める）
        $attendances = $this->statsUser
            ->attendances()
            ->with('dbSetlist.tour')
            ->whereHas('dbSetlist.tour', fn ($q) => $q->where('artist_id', $id)->where('type', '!=', 4))
            ->get();

        $totalShows = $attendances->count();

        $songPlayCounts = [];
        // 同名ツアーを1回だけカウントする版：[songId => [tourTitle1, tourTitle2, ...]]
        $songTourTitles = [];
        foreach ($attendances as $attendance) {
            $setlist = $attendance->dbSetlist;
            if (!$setlist) {
                continue;
            }
            // 1つのセットリスト（＝1回のライブ参加）内で同じ曲が複数回演奏されても1回とカウントする
            $songsInThisSetlist = [];
            $tourTitle = $setlist->tour->title ?? 'Unknown';
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    $songId = (int) $s['song'];
                    if (!in_array($songId, $songsInThisSetlist, true)) {
                        $songsInThisSetlist[] = $songId;
                        $songPlayCounts[$songId] = ($songPlayCounts[$songId] ?? 0) + 1;

                        if (!isset($songTourTitles[$songId])) {
                            $songTourTitles[$songId] = [];
                        }
                        if (!in_array($tourTitle, $songTourTitles[$songId], true)) {
                            $songTourTitles[$songId][] = $tourTitle;
                        }
                    }
                }
            }
        }
        arsort($songPlayCounts);

        $songPlayCountsUnique = [];
        foreach ($songTourTitles as $songId => $tourTitles) {
            $songPlayCountsUnique[$songId] = count($tourTitles);
        }
        arsort($songPlayCountsUnique);

        $songs = DbSong::whereIn('id', array_keys($songPlayCounts))->get()->keyBy('id');

        $buildAllSongs = function (array $counts) use ($songs) {
            $result = [];
            foreach ($counts as $songId => $count) {
                $song = $songs->get($songId);
                if ($song) {
                    $result[] = [
                        'song_id' => 'official-' . $songId,
                        'title' => $song->title,
                        'count' => $count,
                    ];
                }
            }
            return $result;
        };

        $allSongs = $buildAllSongs($songPlayCounts);
        $allSongsUnique = $buildAllSongs($songPlayCountsUnique);

        $totalSongs = count($allSongs);

        $yearStats = $attendances
            ->filter(fn ($a) => $a->attended_date)
            ->groupBy(fn ($a) => $a->attended_date->format('Y'))
            ->map(fn ($group, $year) => (object) ['year' => $year, 'count' => $group->count()])
            // 回数の多い順（同じ回数なら新しい年から）。Yuki の stats と同じ
            ->sortKeysDesc()
            ->sortByDesc('count')
            ->values();

        $venueStats = $attendances
            ->filter(fn ($a) => $a->venue)
            ->groupBy('venue')
            ->map(fn ($group, $venue) => (object) ['venue' => $venue, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();

        $artistRef = 'official-' . $artist->id;
        // 曲が登録されていないアーティストはスタンプ帳のボタンを出さない（null）
        $stampsRoute = \App\Models\DbSong::where('artist_id', $artist->id)->exists()
            ? route('mypage.stats.stamps', $this->isOwner() ? $artistRef : ['artistId' => $artistRef, 'user' => $this->statsUser->id])
            : null;
        $isOwner = $this->isOwner();
        $statsUser = $this->statsUser;
        $tabArtists = $this->tabArtists($artistRef);

        // トピックス（最近初めて聴いた曲・久しぶりに聴いた曲・自分が聴いた「久しぶり」・初めて聴いた曲の年表）
        $heard = $attendances->filter(fn ($a) => $a->dbSetlist)->map(fn ($a) => [
            'date' => $a->attended_date ? $a->attended_date->format('Y-m-d') : substr((string) optional($a->dbSetlist->tour)->date1, 0, 10),
            'title' => optional($a->dbSetlist->tour)->title,
            'url' => route('mypage.attendances.show', ['attendance' => $a, 'from' => 'stats']),
            'song_ids' => collect(array_merge($a->dbSetlist->setlist ?? [], $a->dbSetlist->encore ?? []))
                ->filter(fn ($s) => is_numeric($s['song'] ?? null))->map(fn ($s) => (int) $s['song'])->values()->all(),
        ])->values()->all();
        $topics = new \App\Support\ArtistTopics((int) $artist->id);
        $topicHeardRevivals = $topics->heardRevivals($heard);
        $topicFirstHeard = $topics->firstHeardTimeline($heard);
        $topicWelcomeBack = $topics->welcomeBack($heard);
        $topicRecentFirst = $topics->recentFirstListens($heard);
        $topicRecentFirstAll = $topics->recentFirstListens($heard, false);

        return view('mypage.stats.artist', compact(
            'artist',
            'artistRef',
            'stampsRoute',
            'isOwner',
            'statsUser',
            'tabArtists',
            'totalShows',
            'totalSongs',
            'allSongs',
            'allSongsUnique',
            'yearStats',
            'venueStats',
            'topicHeardRevivals',
            'topicFirstHeard',
            'topicWelcomeBack',
            'topicRecentFirst',
            'topicRecentFirstAll'
        ));
    }

    private function userArtistStats($id)
    {
        $artist = UserArtist::find($id);
        if (!$artist) {
            abort(404);
        }

        $attendances = $this->statsUser
            ->attendances()
            ->with('userSetlist.concert')
            ->whereHas('userSetlist.concert', fn ($q) => $q->where('user_artist_id', $id))
            ->get();

        $totalShows = $attendances->count();

        $songPlayCounts = [];
        $songTourTitles = [];
        foreach ($attendances as $attendance) {
            $setlist = $attendance->userSetlist;
            if (!$setlist) {
                continue;
            }
            $songsInThisSetlist = [];
            $tourTitle = $setlist->concert->title ?? 'Unknown';
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    $songId = (int) $s['song'];
                    if (!in_array($songId, $songsInThisSetlist, true)) {
                        $songsInThisSetlist[] = $songId;
                        $songPlayCounts[$songId] = ($songPlayCounts[$songId] ?? 0) + 1;

                        if (!isset($songTourTitles[$songId])) {
                            $songTourTitles[$songId] = [];
                        }
                        if (!in_array($tourTitle, $songTourTitles[$songId], true)) {
                            $songTourTitles[$songId][] = $tourTitle;
                        }
                    }
                }
            }
        }
        arsort($songPlayCounts);

        $songPlayCountsUnique = [];
        foreach ($songTourTitles as $songId => $tourTitles) {
            $songPlayCountsUnique[$songId] = count($tourTitles);
        }
        arsort($songPlayCountsUnique);

        $songs = UserSong::whereIn('id', array_keys($songPlayCounts))->get()->keyBy('id');

        $buildAllSongs = function (array $counts) use ($songs) {
            $result = [];
            foreach ($counts as $songId => $count) {
                $song = $songs->get($songId);
                if ($song) {
                    $result[] = [
                        'song_id' => 'user-' . $songId,
                        'title' => $song->title,
                        'count' => $count,
                    ];
                }
            }
            return $result;
        };

        $allSongs = $buildAllSongs($songPlayCounts);
        $allSongsUnique = $buildAllSongs($songPlayCountsUnique);

        $totalSongs = count($allSongs);

        $yearStats = $attendances
            ->filter(fn ($a) => $a->attended_date)
            ->groupBy(fn ($a) => $a->attended_date->format('Y'))
            ->map(fn ($group, $year) => (object) ['year' => $year, 'count' => $group->count()])
            // 回数の多い順（同じ回数なら新しい年から）。Yuki の stats と同じ
            ->sortKeysDesc()
            ->sortByDesc('count')
            ->values();

        $venueStats = $attendances
            ->filter(fn ($a) => $a->venue)
            ->groupBy('venue')
            ->map(fn ($group, $venue) => (object) ['venue' => $venue, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();

        $artistRef = 'user-' . $artist->id;
        // 曲が登録されていないアーティストはスタンプ帳のボタンを出さない（null）
        $stampsRoute = \App\Models\UserSong::where('user_artist_id', $artist->id)->exists()
            ? route('mypage.stats.stamps', $this->isOwner() ? $artistRef : ['artistId' => $artistRef, 'user' => $this->statsUser->id])
            : null;
        $isOwner = $this->isOwner();
        $statsUser = $this->statsUser;
        $tabArtists = $this->tabArtists($artistRef);

        // トピックス（公式アーティストと同じ。登録したシングル・アルバムの発売日で新曲を判定する）
        $heard = $attendances->filter(fn ($a) => $a->userSetlist)->map(fn ($a) => [
            'date' => $a->attended_date ? $a->attended_date->format('Y-m-d') : substr((string) optional($a->userSetlist->concert)->date1, 0, 10),
            'title' => optional($a->userSetlist->concert)->title,
            'url' => route('mypage.attendances.show', ['attendance' => $a, 'from' => 'stats']),
            'song_ids' => collect(array_merge($a->userSetlist->setlist ?? [], $a->userSetlist->encore ?? []))
                ->filter(fn ($s) => is_numeric($s['song'] ?? null))->map(fn ($s) => (int) $s['song'])->values()->all(),
        ])->values()->all();
        $topics = new \App\Support\ArtistTopics((int) $artist->id, true);
        $topicHeardRevivals = $topics->heardRevivals($heard);
        $topicFirstHeard = $topics->firstHeardTimeline($heard);
        $topicWelcomeBack = $topics->welcomeBack($heard);
        $topicRecentFirst = $topics->recentFirstListens($heard);
        $topicRecentFirstAll = $topics->recentFirstListens($heard, false);
        // トピックスの曲・ツアーのリンク先はマイページのページ
        $topicSongUrl = fn ($songId) => route('mypage.user_songs.show', $songId);
        $topicTourUrl = fn ($tourId) => route('mypage.user_concerts.show', $tourId);

        return view('mypage.stats.artist', compact(
            'artist',
            'artistRef',
            'stampsRoute',
            'isOwner',
            'statsUser',
            'tabArtists',
            'totalShows',
            'totalSongs',
            'allSongs',
            'allSongsUnique',
            'yearStats',
            'venueStats',
            'topicHeardRevivals',
            'topicFirstHeard',
            'topicWelcomeBack',
            'topicRecentFirst',
            'topicRecentFirstAll',
            'topicSongUrl',
            'topicTourUrl'
        ));
    }

    // アーティストのdatabase楽曲カタログを台紙にしたスタンプ帳。
    // $artistId は "official-{id}" / "user-{id}" 形式。
    // ?user={id} クエリで他ユーザーのスタンプ帳も閲覧できる（プロフィールページのリンク先）。
    // 省略時はログイン中の自分。
    public function stamps(Request $request, $artistId)
    {
        [$kind, $id] = $this->splitRef($artistId);

        $externalUser = $this->statsUser = $this->resolveStatsUser($request);

        if ($kind === 'official') {
            return $this->officialStamps($id, $externalUser);
        }

        return $this->userStamps($id, $externalUser);
    }

    // 判定基準はStatsController::getStampBookと同じ考え方だが、「演奏済み」の元データが
    // SlSetlist（Yuki本人の記録）ではなく、自分が記録したExternalUserAttendanceになる。
    // db_setlistsはSlSetlistと違いフェス形式（block/interleaved）を持たず、songキーが
    // 直接db_song_idを指すため、SlSong経由の変換やフェス展開は不要でシンプルになる。
    private function officialStamps($artistId, $externalUser)
    {
        $artist = Artist::find($artistId);
        if (!$artist) {
            abort(404);
        }

        $attendedSetlistIds = $externalUser
            ->attendances()
            ->whereHas('dbSetlist.tour', fn ($q) => $q->where('artist_id', $artistId))
            ->pluck('db_setlist_id');

        $setlists = DbSetlist::whereIn('id', $attendedSetlistIds)->with('tour')->get();

        // type=0（ツアー）・1（単発ライブ）以外（イベント・ap bank fes・ソロ）はFES扱いとし、
        // そちらでしか演奏されていない曲は台紙上で区別できるようにする
        $playedDbSongIdsNormal = [];
        $playedDbSongIdsFes = [];
        // 福山雅治のDOUBLE ENCORE（2つ目以降のアンコール）は弾き語りなので、そこでしか聴いていない曲はギター柄のスタンプにする
        $playedDbSongIdsDoubleEncore = [];
        $playedDbSongIdsOutsideDoubleEncore = [];
        foreach ($setlists as $setlist) {
            $isFes = !in_array((int) ($setlist->tour->type ?? 0), [0, 1], true);
            $encore = array_values((array) ($setlist->encore ?? []));
            $encoreBlocks = \App\Support\EncoreBlocks::blockIndexes($encore);
            foreach (array_merge($setlist->setlist ?? [], $encore) as $position => $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    if ($isFes) {
                        $playedDbSongIdsFes[(int) $s['song']] = true;
                    } else {
                        $playedDbSongIdsNormal[(int) $s['song']] = true;
                    }
                    $encoreIndex = $position - count($setlist->setlist ?? []);
                    if ($encoreIndex >= 0 && ($encoreBlocks[$encoreIndex] ?? 0) >= \App\Support\EncoreBlocks::DOUBLE_ENCORE) {
                        $playedDbSongIdsDoubleEncore[(int) $s['song']] = true;
                    } else {
                        $playedDbSongIdsOutsideDoubleEncore[(int) $s['song']] = true;
                    }
                }
            }
        }

        $playedDbSongIds = $playedDbSongIdsNormal + $playedDbSongIdsFes;
        $fesOnlyDbSongIds = array_diff_key($playedDbSongIdsFes, $playedDbSongIdsNormal);
        $hikigatariDbSongIds = (int) $artistId === \App\Support\EncoreBlocks::HIKIGATARI_ARTIST_ID
            ? array_diff_key($playedDbSongIdsDoubleEncore, $playedDbSongIdsOutsideDoubleEncore)
            : [];

        $everPerformedDbSongIds = $this->everPerformedDbSongIds((int) $artistId);

        $stampFilters = $this->stampDiscographyFilters((int) $artistId);

        $dbSongs = DbSong::where('artist_id', $artistId)->orderBy('sort_order')->get();
        $stamps = $dbSongs->map(function (DbSong $song) use ($playedDbSongIds, $everPerformedDbSongIds, $fesOnlyDbSongIds, $hikigatariDbSongIds, $stampFilters) {
            return [
                'song_id' => $song->id,
                'song_url' => $this->isOwner() ? route('mypage.attendances.index', ['song_id' => 'official-' . $song->id]) : url('/database/songs/' . $song->id),
                'title' => $song->title,
                'done' => isset($playedDbSongIds[$song->id]),
                'never_performed' => !isset($everPerformedDbSongIds[$song->id]),
                'fes_only' => isset($fesOnlyDbSongIds[$song->id]),
                'hikigatari_only' => isset($hikigatariDbSongIds[$song->id]),
                'filter_keys' => $this->stampFilterKeys($song->id, $stampFilters),
                'track_titles' => $this->stampTrackTitles($song->id, $stampFilters),
                'track_orders' => $this->stampTrackOrders($song->id, $stampFilters),
            ];
        });

        return $this->renderStampsView($artist, $stamps, $externalUser, $stampFilters);
    }

    // ユーザー登録アーティストのスタンプ帳。「自分が参加したセットリストで演奏された曲か」
    // に加えて、そのアーティストのmanage画面に登録された全ツアー記録を基準に
    // 「そもそもライブで一度も演奏されていない曲」を never_performed として区別する
    // （公式側のeverPerformedDbSongIdsと同じ考え方。manage画面で全ツアーが登録されて
    // いる前提のため、登録が不完全なアーティストでは正確性が下がる点に留意）。
    // type（0=ツアー・1=単発ライブ以外はフェス扱い）は公式側と同様に区別し、
    // フェスでしか演奏されていない曲はfes_onlyとして台紙上で区別できるようにする。
    private function userStamps($artistId, $externalUser)
    {
        $artist = UserArtist::find($artistId);
        if (!$artist) {
            abort(404);
        }

        $everPerformedUserSongIds = $this->everPerformedUserSongIds((int) $artistId);

        $attendedSetlistIds = $externalUser
            ->attendances()
            ->whereHas('userSetlist.concert', fn ($q) => $q->where('user_artist_id', $artistId))
            ->pluck('user_setlist_id');

        $setlists = UserSetlist::whereIn('id', $attendedSetlistIds)->with('concert')->get();

        $playedUserSongIdsNormal = [];
        $playedUserSongIdsFes = [];
        foreach ($setlists as $setlist) {
            $isFes = !in_array((int) ($setlist->concert->type ?? 0), [0, 1], true);
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    if ($isFes) {
                        $playedUserSongIdsFes[(int) $s['song']] = true;
                    } else {
                        $playedUserSongIdsNormal[(int) $s['song']] = true;
                    }
                }
            }
        }

        $playedUserSongIds = $playedUserSongIdsNormal + $playedUserSongIdsFes;
        $fesOnlyUserSongIds = array_diff_key($playedUserSongIdsFes, $playedUserSongIdsNormal);

        // 登録したシングル・アルバムで絞り込めるようにする（公式のスタンプ帳と同じ）
        $stampFilters = $this->stampDiscographyFilters((int) $artistId, true);

        $userSongs = UserSong::where('user_artist_id', $artistId)->orderBy('sort_order')->get();
        $stamps = $userSongs->map(function (UserSong $song) use ($playedUserSongIds, $everPerformedUserSongIds, $fesOnlyUserSongIds, $stampFilters) {
            return [
                'song_id' => $song->id,
                'song_url' => $this->isOwner() ? route('mypage.attendances.index', ['song_id' => 'user-' . $song->id]) : null,
                'title' => $song->title,
                'done' => isset($playedUserSongIds[$song->id]),
                'never_performed' => !isset($everPerformedUserSongIds[$song->id]),
                'fes_only' => isset($fesOnlyUserSongIds[$song->id]),
                'filter_keys' => $this->stampFilterKeys($song->id, $stampFilters),
                'track_titles' => $this->stampTrackTitles($song->id, $stampFilters),
                'track_orders' => $this->stampTrackOrders($song->id, $stampFilters),
            ];
        });

        return $this->renderStampsView($artist, $stamps, $externalUser, $stampFilters);
    }

    private function renderStampsView($artist, $stamps, $externalUser, ?array $stampFilters = null)
    {
        $totalCount = $stamps->count();
        $doneCount = $stamps->where('done', true)->count();
        $percentage = $totalCount > 0 ? round(($doneCount / $totalCount) * 100, 1) : 0;

        $performedCount = $stamps->where('never_performed', false)->count();
        $performedPercentage = $performedCount > 0 ? round(($doneCount / $performedCount) * 100, 1) : 0;

        $isOwner = $externalUser->id === Auth::guard('external')->id();

        return view('mypage.stats.stamps', compact(
            'artist',
            'stamps',
            'totalCount',
            'doneCount',
            'percentage',
            'performedCount',
            'performedPercentage',
            'externalUser',
            'isOwner',
            'stampFilters'
        ));
    }
}

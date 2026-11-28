<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\SlSetlist;
use App\Models\SlSong;
use Illuminate\Http\Request;

class SlSetlistController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $type = request()->input('type');
        $today = now()->toDateString();

        // これからのライブ（今日以降）
        $upcomingQuery = SlSetlist::where('date', '>=', $today)->orderBy('date', 'asc');

        if ($type === '1') {
            $upcomingQuery->where('fes', 0);
        } elseif ($type === '2') {
            $upcomingQuery->whereIn('fes', [1, 2]);
        }

        $upcomingSetlists = $upcomingQuery->with('artist')->get();
        $upcomingTotalCount = $upcomingSetlists->count();

        // 今までのライブ（今日より前）
        $pastQuery = SlSetlist::where('date', '<', $today)->orderBy('date', 'desc');

        if ($type === '1') {
            $pastQuery->where('fes', 0);
        } elseif ($type === '2') {
            $pastQuery->whereIn('fes', [1, 2]);
        }

        $pastSetlists = $pastQuery->with('artist')->paginate(10);
        $pastTotalCount = $pastSetlists->total();

        // アーティスト選択は Live（単独ライブで見た）/ Fes（フェスでだけ見た）に分ける
        $artistGroups = \App\Support\SetlistArtistGroups::groups();
        $artists = $artistGroups['Live']->merge($artistGroups['Fes']);
        $allArtists = $artists;

        // Setlistから年のリストを取得
        $years = SlSetlist::select('year')
            ->whereNotNull('year')
            ->distinct()
            ->orderBy('year', 'asc')
            ->pluck('year')
            ->map(function ($year) {
                return (object)['year' => $year];
            });

        // AJAXリクエストの場合はJSON形式で返す（過去のライブのみページネーション対応）
        if (request()->wantsJson() || request()->ajax()) {
            $html = view('sl_setlists._list', [
                'setlists' => $pastSetlists,
                'totalCount' => $pastTotalCount,
                'type' => $type
            ])->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $pastSetlists->appends(['type' => $type])->nextPageUrl(),
                'current_page' => $pastSetlists->currentPage(),
                'last_page' => $pastSetlists->lastPage(),
            ]);
        }

        // 検索候補を取得（曲名 + アーティスト名）
        $suggestions = \App\Models\SlSong::query()
            ->leftJoin('artists', 'artists.id', '=', 'sl_songs.artist_id')
            ->orderBy('sl_songs.title', 'asc')
            ->get([
                'sl_songs.id as id',
                'sl_songs.title as title',
                'artists.name as artist_name',
            ])
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'title' => $row->title,
                    'artist_name' => $row->artist_name ?? '',
                ];
            })
            ->toArray();

        // ビューにデータを渡して表示する
        return view('sl_setlists.index', compact('artists', 'allArtists', 'artistGroups', 'upcomingSetlists', 'pastSetlists', 'upcomingTotalCount', 'pastTotalCount', 'years', 'type', 'suggestions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        // メモリリミットを増やす
        ini_set('memory_limit', '256M');

        $setlists = SlSetlist::with('artist')->findOrFail($id);
        $artists = Artist::orderBy('id', 'asc')
        ->get(['id', 'name']); // 必要なカラムのみ取得

        // 年別ページ・アーティストページ・会場検索結果のどれから来たかを ?from= で引き継ぎ、
        // Previous/Nextの移動範囲をその一覧と同じ範囲（年内 / アーティスト内 / 同一会場内）に絞り込む。
        // 該当しない・指定が無い場合は従来通り全セットリスト中で前後に移動する。
        $from = $request->query('from');
        // 曲のページから来た場合は、その曲を演奏したセットリストの中だけで前後に移動する
        // （setlists/songs からは from=slsong-{曲ID}、database/songs の Yuki's Live Attendances からは from=song-{曲ID}）
        $songSetlistIds = null;
        if (preg_match('/^slsong-(\d+)$/', (string) $from, $m) && ($fromSong = \App\Models\SlSong::find($m[1]))) {
            $songSetlistIds = $fromSong->performedSetlists()->pluck('id')->all();
        } elseif (preg_match('/^song-(\d+)$/', (string) $from, $m) && ($fromSong = \App\Models\DbSong::find($m[1]))) {
            $songSetlistIds = $fromSong->performedSlSetlists()->pluck('id')->all();
        }
        $scopeQuery = function ($query) use ($from, $setlists, $songSetlistIds) {
            if ($songSetlistIds !== null) {
                $query->whereIn('id', $songSetlistIds);
            } elseif ($from === 'year') {
                $query->where('year', $setlists->year);
            } elseif ($from === 'artist' && $setlists->artist_id) {
                $query->where('artist_id', $setlists->artist_id);
            } elseif ($from === 'venue' && $setlists->venue) {
                $query->where('venue', $setlists->venue);
            }
            return $query;
        };

        $previous = $scopeQuery(SlSetlist::where(function ($q) use ($setlists) {
                $q->where('date', '<', $setlists->date)
                  ->orWhere(function ($q2) use ($setlists) {
                      $q2->where('date', $setlists->date)->where('id', '<', $setlists->id);
                  });
            }))->orderBy('date', 'desc')->orderBy('id', 'desc')->first();
        $next = $scopeQuery(SlSetlist::where(function ($q) use ($setlists) {
                $q->where('date', '>', $setlists->date)
                  ->orWhere(function ($q2) use ($setlists) {
                      $q2->where('date', $setlists->date)->where('id', '>', $setlists->id);
                  });
            }))->orderBy('date')->orderBy('id')->first();

        $liveStatsByArtist = $this->liveStats($setlists);

        return view('sl_setlists.show', compact('artists', 'setlists', 'previous', 'next', 'from', 'liveStatsByArtist'));
    }

    // Yuki's Setlists の曲のまま（Database の曲に置き換えずに）の、そのアーティストの「聴いた公演」の一覧（ArtistTopics の $heard の形）。
    // そのアーティストのライブの曲と、フェスでそのアーティストが出たステージの曲
    private function slHeard(Artist $artist, $setlists): array
    {
        $heard = [];
        foreach ($setlists as $setlist) {
            $items = (int) $setlist->artist_id === (int) $artist->id ? array_merge($setlist->setlist ?? [], $setlist->encore ?? []) : [];
            if ($setlist->fes) {
                foreach (array_merge((array) ($setlist->fes_setlist ?? []), (array) ($setlist->fes_encore ?? [])) as $block) {
                    if (($block['type'] ?? 'song') === 'block' && (string) ($block['artist'] ?? '') === (string) $artist->id) {
                        $items = array_merge($items, (array) ($block['songs'] ?? []));
                    }
                }
            }
            $songIds = array_values(array_map('intval', array_filter(array_column($items, 'song'), 'is_numeric')));
            if ($songIds) {
                $heard[] = ['date' => substr((string) $setlist->date, 0, 10), 'title' => $setlist->title, 'url' => route('setlists.show', $setlist->id),
                    'song_ids' => $songIds, 'titles' => \App\Support\ArtistTopics::versionTitles($items), 'sl_setlist_id' => $setlist->id];
            }
        }

        return $heard;
    }

    // セトリの下の、その公演の stats（初めて聴いた曲・久しぶりに聴いた曲・久しぶりに演奏された曲・初披露の曲）。Yuki の参加記録（Yuki's Setlists）で見る。
    // アーティストごと（フェスは出演したアーティストごと）。[['artist' => Artist, 'stats' => ArtistTopics::attendanceSummary, 'songUrl', 'tourUrl'], ...]。
    // 「このツアー」は、その日を含む Database のそのアーティスト（組になっているアーティストを含む）のライブ
    private function liveStats(SlSetlist $setlist): array
    {
        if ($setlist->date === null || substr((string) $setlist->date, 0, 10) > now()->toDateString()) {
            return [];
        }
        $artistIds = $setlist->fes
            ? collect(array_merge((array) ($setlist->fes_setlist ?? []), (array) ($setlist->fes_encore ?? [])))
                ->filter(fn ($block) => ($block['type'] ?? 'song') === 'block' && is_numeric($block['artist'] ?? null))
                ->pluck('artist')->map(fn ($id) => (int) $id)->unique()->values()->all()
            : array_filter([(int) $setlist->artist_id]);
        $date = substr((string) $setlist->date, 0, 10);
        $setlistsUntilToday = SlSetlist::where('date', '<=', now()->toDateString())->get();
        $stats = new StatsController();
        $result = [];
        foreach (Artist::whereIn('id', $artistIds)->get() as $artist) {
            // Database に曲が無く、マイページで作ったアーティストとも結び付いていないアーティストは、Yuki's Setlists の曲で
            // 初めて聴いた曲・久しぶりに聴いた曲だけ出す（久しぶりに演奏された曲・初披露の曲は演奏記録が要るので出さない）
            if (!\App\Models\DbSong::where('artist_id', $artist->id)->exists() && !\App\Support\ArtistLink::userArtistFor($artist)) {
                $heard = $this->slHeard($artist, $setlistsUntilToday);
                $show = collect($heard)->firstWhere('sl_setlist_id', $setlist->id);
                if ($show) {
                    $result[] = [
                        'artist' => $artist,
                        'stats' => \App\Support\ArtistTopics::personalListens($show, $heard, SlSong::where('artist_id', $artist->id)->pluck('title', 'id')->all()) + ['returns' => [], 'debuts' => [], 'releaseYears' => []],
                        'songUrl' => fn ($id) => url('/setlists/songs/' . $id),
                        'tourUrl' => fn ($id) => route('setlists.show', $id),
                    ];
                }
                continue;
            }
            ['heard' => $heard, 'topics' => $topics, 'topicUserArtist' => $userArtist] = $stats->yukiHeard($artist, $setlistsUntilToday);
            $show = collect($heard)->firstWhere('sl_setlist_id', $setlist->id);
            if (!$show) {
                continue;
            }
            $show['tour_id'] = $userArtist
                ? null
                : \App\Models\DbConcert::whereIn('artist_id', Artist::songArtistIds($artist->id))
                    ->whereDate('date1', '<=', $date)
                    ->where(fn ($q) => $q->whereDate('date2', '>=', $date)->orWhere(fn ($q2) => $q2->whereNull('date2')->whereDate('date1', $date)))
                    ->orderByDesc('date1')->value('id');
            $result[] = [
                'artist' => $artist,
                'stats' => $topics->attendanceSummary($show, $heard),
                // マイページで作ったアーティストと結び付いた曲は、マイページの曲・ライブのページへ（アーティストの stats と同じ）
                'songUrl' => $userArtist ? fn ($id) => route('mypage.user_songs.show', ['id' => $id, 'tab' => 'mine']) : fn ($id) => route('songs.show', ['id' => $id, 'tab' => 'mine']),
                'tourUrl' => $userArtist ? fn ($id) => route('mypage.user_concerts.show', $id) : fn ($id) => route('live.show', $id),
            ];
        }

        return $result;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

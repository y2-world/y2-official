<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\DbSong;
use App\Models\DbConcert;
use App\Models\DbSetlist;
use App\Models\DbSetlistRow;
use Illuminate\Http\Request;

class DbConcertController extends Controller
{
    private const SUMMARY_WITHOUT_DIFFERENCES_TOUR_IDS = [47, 218, 220];

    // 一覧の絞り込み（?type=）→ そこに出すライブの type。1 = Live（ツアーと単発ライブ）、6 = Tours、5 = 単発ライブ、2〜4 はそのまま
    // マイページのライブ一覧（UserConcertController）でも同じ ?type= を使う
    public static function listTypes($type): ?array
    {
        return match ((string) $type) {
            '1' => [0, 1],
            '6' => [0],
            '5' => [1],
            '2', '3', '4' => [(int) $type],
            default => null,
        };
    }

    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $type = request()->input('type');

        $liveQuery = DbConcert::where('artist_id', $artistId)->orderBy('date1', 'desc');

        if ($types = self::listTypes($type)) {
            $liveQuery->whereIn('type', $types);
        }

        $bios = $artist->years;

        $tours = $liveQuery->paginate(10);
        $totalCount = $tours->total();

        if (request()->wantsJson() || request()->ajax()) {
            $html = view('db_concerts._list', compact('tours', 'totalCount', 'type'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $tours->appends(['type' => $type])->nextPageUrl(),
                'current_page' => $tours->currentPage(),
                'last_page' => $tours->lastPage(),
            ]);
        }

        return view('db_concerts.index', compact('tours', 'bios', 'type', 'totalCount', 'artist'));
    }

    // 会場（Venues）。ツアー・ライブのスケジュールに出てくる会場の一覧。?keyword=会場名 のときは、その会場の公演（日付とツアー）の一覧
    public function venues($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $keyword = trim((string) request()->input('keyword'));
        // 会場の一覧も、会場ごとの公演も、ツアーと単発ライブだけ（venuePageEntries）
        $entries = DbConcert::venuePageEntries((int) $artistId);

        if ($keyword !== '') {
            // 前の名前・書き方の違い・ほかのアーティストの会場名で開いたときは、このアーティストの会場名のページへ
            $current = DbConcert::venuePageName($artist->id, $keyword);
            if ($current !== null && $current !== $keyword) {
                return redirect()->route('database.venues', [$artist->id, 'keyword' => $current]);
            }
            // 前の名前・書き方の違いの公演もまとめて出す（今の会場名で）
            $performances = self::venueShows($keyword, $entries)->reverse()->values();

            return view('db_concerts.venue', compact('artist', 'keyword', 'performances'));
        }

        // 公演の多い順、同じ数なら最後の公演の新しい順
        $venues = $entries->groupBy('venue')
            ->map(fn ($rows, $venue) => (object) ['venue' => $venue, 'count' => $rows->count(), 'last' => $rows->max('date')])
            ->sort(fn ($a, $b) => [$b->count, $b->last] <=> [$a->count, $a->last])
            ->values();

        return view('db_concerts.venues', compact('artist', 'venues'));
    }

    // 1つの会場の公演（古い順）。url はその公演のパターンを開くリンクで、Previous/Next が同じ会場の前後の公演になるよう
    // from=venue・会場名・公演日（show）を付ける。公演日に当たるパターンが無ければ、ライブのページの頭
    private static function venueShows(string $venue, \Illuminate\Support\Collection $entries): \Illuminate\Support\Collection
    {
        // 同じ日の昼・夜などは、スケジュールに書いてある順
        $shows = $entries->where('venue', $venue)
            ->map(fn ($entry, $index) => $entry + ['index' => $index])
            ->sort(fn ($a, $b) => [$a['date'], $a['index']] <=> [$b['date'], $b['index']])
            ->values();
        $setlists = DbSetlist::whereIn('tour_id', $shows->pluck('concert.id')->unique()->all())
            ->orderBy('row')->orderBy('order_no')->get();
        $patternOf = [];
        foreach (\App\Support\PatternShows::of($setlists) as $patternId => $keys) {
            foreach ($keys as $key) {
                $patternOf[$key] ??= $patternId;
            }
        }

        return $shows->map(function ($show) use ($venue, $patternOf) {
            $patternId = $patternOf[$show['concert']->id . '|' . $show['key']] ?? null;
            $query = ['view' => 'standard', 'from' => 'venue', 'venue' => $venue, 'show' => $show['key']]
                + ($patternId ? ['pattern' => 'setlist-pattern-' . $patternId] : []);

            return $show + ['url' => route('live.show', $show['concert']->id) . '?' . http_build_query($query)];
        });
    }

    public function show(Request $request, $id)
    {
        $tours = DbConcert::findOrFail($id);
        $artist = $tours->artist;

        // 一覧ページ（type別に絞り込まれたタブ）から来た場合、Previous/Nextの移動範囲を
        // その一覧と同じtype（ツアー/イベント/ap bank fes/ソロ）内に絞り込む。
        // 該当しない・指定が無い場合は従来通りアーティスト内の全ライブから前後に移動する。
        $from = $request->query('from');
        $tab = $request->query('tab');
        // 曲のページ（database/songs）から来た場合（from=song-{曲ID}）は、その曲を演奏したライブの中だけで前後に移動する
        $songTourIds = null;
        if (preg_match('/^song-(\d+)$/', (string) $from, $m) && ($fromSong = DbSong::find($m[1]))) {
            $songTourIds = $fromSong->performedTourSetlists()->pluck('tour_id')->unique()->values()->all();
        }
        // from=type-{一覧の type}：その一覧と同じ範囲（Live ならツアーと単発ライブ）。from=type は前からのリンク用で、開いたライブと同じ type
        $listTypes = preg_match('/^type-(\d+)$/', (string) $from, $m) ? self::listTypes($m[1]) : null;
        $scopeQuery = function ($query) use ($from, $tours, $songTourIds, $listTypes) {
            if ($listTypes !== null) {
                $query->whereIn('type', $listTypes);
            } elseif ($from === 'type') {
                $query->where('type', $tours->type);
            } elseif ($songTourIds !== null) {
                $query->whereIn('id', $songTourIds);
            }
            return $query;
        };
        $songs = DbSong::orderBy('sort_order', 'asc')->get();
        $tourSetlists = DbSetlist::where('tour_id', $id)->orderBy('order_no', 'asc')->get();

        // Summaryテキスト表示（tab=summary）で、各rowを「Row 1」のような機械的な
        // 番号ではなく、そのrowに設定されたグループ名（例: 「ホール・アリーナ公演」）
        // で見出しを付けるための、row => タイトル一覧（重複除去、登場順）のマップ。
        // 同じrow内で日替わりの途中からグループタイトルが切り替わることがある
        // （例: tour155のrow1が「アリーナ公演」→「ドーム・スタジアム公演」）ため、
        // そのrowで使われている全タイトルを保持する（表示側で1件なら単独見出し、
        // 複数件ならすべて列挙する）。
        $summaryRowTitles = DbSetlistRow::where('tour_id', $id)
            ->orderBy('order_no')
            ->get()
            ->groupBy('row')
            ->map(fn ($rows) => $rows->pluck('title')->unique()->values());

        // Summary（マイページのライブのページと共通）
        ['setlistSummaries' => $setlistSummaries, 'summaryRows' => $summaryRows, 'summaryPickedPatterns' => $summaryPickedPatterns]
            = \App\Support\SetlistSummaries::build($tourSetlists, $songs, in_array((int) $tours->id, self::SUMMARY_WITHOUT_DIFFERENCES_TOUR_IDS, true));

        $previousQuery = $scopeQuery(DbConcert::where('artist_id', $tours->artist_id)
            ->where(function ($q) use ($tours) {
                $q->where('date1', '<', $tours->date1)
                  ->orWhere(function ($q2) use ($tours) {
                      $q2->where('date1', $tours->date1)->where('id', '<', $tours->id);
                  });
            }))->orderBy('date1', 'desc')->orderBy('id', 'desc');
        $nextQuery = $scopeQuery(DbConcert::where('artist_id', $tours->artist_id)
            ->where(function ($q) use ($tours) {
                $q->where('date1', '>', $tours->date1)
                  ->orWhere(function ($q2) use ($tours) {
                      $q2->where('date1', $tours->date1)->where('id', '>', $tours->id);
                  });
            }))->orderBy('date1')->orderBy('id');

        // Previous/NextはSummaryの有無に関係なく、隣のライブに移動する
        $previous = $previousQuery->first();
        $next = $nextQuery->first();

        // 会場のページから来たとき（from=venue）は、同じ会場の前後の公演（同じツアーのほかの日も）へ
        $previousUrl = $nextUrl = null;
        if ($from === 'venue' && ($venue = (string) $request->query('venue')) !== '') {
            $venueShows = self::venueShows($venue, DbConcert::venuePageEntries((int) $tours->artist_id));
            $index = $venueShows->search(fn ($show) => $show['concert']->id === $tours->id && $show['key'] === (string) $request->query('show'));
            if ($index === false) {
                $index = $venueShows->search(fn ($show) => $show['concert']->id === $tours->id);
            }
            if ($index !== false) {
                $previous = $next = null;
                $previousUrl = $venueShows[$index - 1]['url'] ?? null;
                $nextUrl = $venueShows[$index + 1]['url'] ?? null;
            }
        }

        // Summaryを見ながらPrevious/Nextで移動している間は、Summaryの無いライブを通っても
        // その先のライブでSummaryに戻れるよう、Summaryで見ている状態（?tab=summary）を引き継ぐ。
        // Summaryの無いライブ自体は通常の表示で開く（Summaryで開くと中身が無いため）
        $summaryMode = $tab === 'summary';
        if ($summaryMode && $setlistSummaries->isEmpty()) {
            $tab = null;
        }

        return view('db_concerts.show', compact('songs', 'previous', 'next', 'previousUrl', 'nextUrl', 'summaryMode', 'tours', 'tourSetlists', 'artist', 'setlistSummaries', 'summaryRows', 'from', 'tab', 'summaryRowTitles', 'summaryPickedPatterns'));
    }
}

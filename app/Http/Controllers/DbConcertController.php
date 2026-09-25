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
    // Setlist Summarize（複数パターンのセットリストを1つの比較表にまとめる機能）が
    // 実際に表示すべき差異を持つかどうかを判定する。show()の$setlistSummaries算出と
    // 同じロジックを、type=summary一覧の絞り込みでも再利用するための共通処理。
    private function tourHasSetlistSummary(DbConcert $tour, $songs): bool
    {
        $tourSetlists = DbSetlist::where('tour_id', $tour->id)->orderBy('order_no', 'asc')->get();
        $forceSimpleEncoreMerge = (int) $tour->artist_id === 5;

        return $tourSetlists
            ->groupBy(fn ($m) => $m->row ?? 1)
            ->contains(function ($rowSetlists) use ($songs, $forceSimpleEncoreMerge) {
                if ($rowSetlists->count() < 2) {
                    return false;
                }
                $summary = buildSetlistPatternSummary($rowSetlists, $songs, $forceSimpleEncoreMerge);
                $hasVariantDifference = collect(array_merge($summary['setlist'], $summary['encore']))
                    ->contains(fn ($row) => count($row['variants']) > 1);
                $setlistCounts = $rowSetlists->map(fn ($s) => count($s->setlist ?? []));
                $encoreCounts = $rowSetlists->map(fn ($s) => count($s->encore ?? []));
                $totalCounts = $rowSetlists->map(fn ($s) => count($s->setlist ?? []) + count($s->encore ?? []));
                $hasCountDifference = $setlistCounts->unique()->count() > 1
                    || $encoreCounts->unique()->count() > 1
                    || $totalCounts->unique()->count() > 1;

                return $hasVariantDifference || $hasCountDifference;
            });
    }

    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $type = request()->input('type');

        $liveQuery = DbConcert::where('artist_id', $artistId)->orderBy('date1', 'desc');

        if ($type === '1') {
            $liveQuery->whereIn('type', [0, 1]);
        } elseif ($type === '6') {
            $liveQuery->where('type', 0);
        } elseif ($type === '5') {
            $liveQuery->where('type', 1);
        } elseif ($type === '2') {
            $liveQuery->where('type', 2);
        } elseif ($type === '3') {
            $liveQuery->where('type', 3);
        } elseif ($type === '4') {
            $liveQuery->where('type', 4);
        }

        $bios = $artist->years;

        if ($type === 'summary') {
            // SummaryがあるかどうかはDbSetlistのJSON内容を実際に比較しないと
            // 判定できないため、DBのwhere句だけでは絞り込めない。対象アーティストの
            // 全ライブを取得してPHP側でフィルタしてから手動でページングする
            // （ページ内の件数がフィルタ後に変わるため、通常のpaginate()は使えない）。
            $songs = DbSong::orderBy('sort_order', 'asc')->get();
            $allTours = $liveQuery->get()->filter(fn ($tour) => $this->tourHasSetlistSummary($tour, $songs))->values();
            $totalCount = $allTours->count();
            $perPage = 10;
            $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
            $tours = new \Illuminate\Pagination\LengthAwarePaginator(
                $allTours->forPage($currentPage, $perPage),
                $totalCount,
                $perPage,
                $currentPage,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
        } else {
            $tours = $liveQuery->paginate(10);
            $totalCount = $tours->total();
        }

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

    public function show(Request $request, $id)
    {
        $tours = DbConcert::findOrFail($id);
        $artist = $tours->artist;

        // 一覧ページ（type別に絞り込まれたタブ）から来た場合、Previous/Nextの移動範囲を
        // その一覧と同じtype（ツアー/イベント/ap bank fes/ソロ）内に絞り込む。
        // 該当しない・指定が無い場合は従来通りアーティスト内の全ライブから前後に移動する。
        $from = $request->query('from');
        $tab = $request->query('tab');
        $scopeQuery = function ($query) use ($from, $tours) {
            if ($from === 'type') {
                $query->where('type', $tours->type);
            }
            return $query;
        };
        $songs = DbSong::orderBy('sort_order', 'asc')->get();
        $tourSetlists = DbSetlist::where('tour_id', $id)->orderBy('order_no', 'asc')->get();

        // Summaryテキスト表示（tab=summary）で、各rowを「Row 1」のような機械的な
        // 番号ではなく、そのrowに設定されたグループ名（例: 「ホール・アリーナ公演」）
        // で見出しを付けるための、row => 代表タイトルのマップ。同じrow内で複数の
        // order_noにタイトルが設定されていることがあるが、ここではrow全体を
        // 代表する見出しが欲しいだけなので、そのrowで最も早いorder_noのタイトルを使う。
        $summaryRowTitles = DbSetlistRow::where('tour_id', $id)
            ->orderBy('order_no')
            ->get()
            ->groupBy('row')
            ->map(fn ($rows) => $rows->first()->title);

        // 福山雅治（artist_id=5）はツアーによってアンコールの構成が公演ごとに
        // 大きく異なり、かつ同じ曲（例: MELODY）が全公演共通のアンカーとして
        // 存在するため、LCSアンカー方式だとアンカー前後の曲を誤って別の日替わり
        // 位置に押し込め合い、崩壊した表示になってしまう。アンコールだけ常に
        // 単純な位置ベースマージを使う。
        $forceSimpleEncoreMerge = (int) $artist->id === 5;

        // row（同時に見比べる列同士）ごとに、パターンが2つ以上ある場合だけ
        // Summarizeポップアップ用の位置ベース差分マージ結果を作る
        $setlistSummaries = $tourSetlists
            ->groupBy(fn ($m) => $m->row ?? 1)
            ->map(function ($rowSetlists) use ($songs, $forceSimpleEncoreMerge) {
                if ($rowSetlists->count() < 2) {
                    return null;
                }
                $summary = buildSetlistPatternSummary($rowSetlists, $songs, $forceSimpleEncoreMerge);
                // variantsが2件以上の行があれば、当然差異あり。
                // それに加えて、パターン間で曲数（setlist+encoreの総数）が異なる場合も
                // 差異ありとみなす。曲数がバラバラだと、日替わり箇所ごとの曲数の違いに
                // より1つもペア化されない（=全部variants1件の単独行になる）ことがあるが、
                // それは差異が無いのではなく、位置合わせで安全側に倒した結果でしかないため。
                $hasVariantDifference = collect(array_merge($summary['setlist'], $summary['encore']))
                    ->contains(fn ($row) => count($row['variants']) > 1);
                // setlist単体・encore単体それぞれの曲数比較に加え、合計曲数も見る。
                // 本編/アンコールの境界自体がパターン間でズレるケース（例: ある公演だけ
                // 本編最後の曲が翌日はアンコール1曲目になる）は、setlist・encore単体の
                // 曲数だけが食い違い合計は一致することがあるため、単体の比較が必須。
                $setlistCounts = $rowSetlists->map(fn ($s) => count($s->setlist ?? []));
                $encoreCounts = $rowSetlists->map(fn ($s) => count($s->encore ?? []));
                $totalCounts = $rowSetlists->map(fn ($s) => count($s->setlist ?? []) + count($s->encore ?? []));
                $hasCountDifference = $setlistCounts->unique()->count() > 1
                    || $encoreCounts->unique()->count() > 1
                    || $totalCounts->unique()->count() > 1;
                $hasDifference = $hasVariantDifference || $hasCountDifference;
                // 全パターンが完全に同じ曲順・曲目のrowは、Summarizeで見せる差異が
                // 無いので対象から除外する
                return $hasDifference ? $summary : null;
            })
            ->filter();
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

        if ($tab === 'summary') {
            // Summary一覧内を移動している間は、Previous/NextもSummaryを持つ
            // 公演までスキップして探す（Summaryが無い公演に移動してしまうと
            // 「このライブにはSummaryがありません」の空表示になってしまうため）。
            $previous = $previousQuery->get()->first(fn ($t) => $this->tourHasSetlistSummary($t, $songs));
            $next = $nextQuery->get()->first(fn ($t) => $this->tourHasSetlistSummary($t, $songs));
        } else {
            $previous = $previousQuery->first();
            $next = $nextQuery->first();
        }

        return view('db_concerts.show', compact('songs', 'previous', 'next', 'tours', 'tourSetlists', 'artist', 'setlistSummaries', 'from', 'tab', 'summaryRowTitles'));
    }
}

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

    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $type = request()->input('type');
        // 管理画面で「イベントを含める」を出すにしたアーティスト（フェス・イベントの多いアーティスト）は、最初はイベントを除いた「Live」を出す。All は type=all
        if ($type === null && $artist->stats_event_toggle) {
            $type = '1';
        }

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

        // row（同時に見比べる列同士）ごとに、パターンが2つ以上ある場合にSummaryを作る。
        $setlistSummaries = $tourSetlists
            ->groupBy(fn ($m) => $m->row ?? 1)
            ->sortKeys()
            ->map(function ($rowSetlists) use ($songs, $tours) {
                if ($rowSetlists->count() < 2) {
                    return null;
                }
                $summary = buildSetlistPatternSummary($rowSetlists, $songs);
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
                if ($hasDifference) {
                    return $summary;
                }

                if (!in_array((int) $tours->id, self::SUMMARY_WITHOUT_DIFFERENCES_TOUR_IDS, true)) {
                    return null;
                }

                // 全パターンが同じ曲順・曲目の場合は、同じ行を再マージせず、
                // 指定公演についてはorder_noが最後のパターンをそのままSummaryに使う。
                $finalPattern = $rowSetlists->sortBy('order_no')->last();
                return buildSetlistPatternSummary(collect([$finalPattern]), $songs);
            })
            ->filter();

        // Summaryページでは差異のあるrowだけでなく、同じ曲順のrowも含めて
        // ツアー内のすべてのrowを表示する。差異のあるrowは上で計算した結果を再利用し、
        // Summary対象外だったrowだけ単独パターンを含めて構築する。
        $summaryRows = $tourSetlists
            ->groupBy(fn ($m) => $m->row ?? 1)
            ->sortKeys()
            ->map(function ($rowSetlists, $rowNum) use ($setlistSummaries, $songs) {
                return $setlistSummaries->get($rowNum)
                    ?? buildSetlistPatternSummary($rowSetlists, $songs);
            });
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

        // Summaryを見ながらPrevious/Nextで移動している間は、Summaryの無いライブを通っても
        // その先のライブでSummaryに戻れるよう、Summaryで見ている状態（?tab=summary）を引き継ぐ。
        // Summaryの無いライブ自体は通常の表示で開く（Summaryで開くと中身が無いため）
        $summaryMode = $tab === 'summary';
        if ($summaryMode && $setlistSummaries->isEmpty()) {
            $tab = null;
        }

        return view('db_concerts.show', compact('songs', 'previous', 'next', 'summaryMode', 'tours', 'tourSetlists', 'artist', 'setlistSummaries', 'summaryRows', 'from', 'tab', 'summaryRowTitles'));
    }
}

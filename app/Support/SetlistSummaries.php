<?php

namespace App\Support;

use Illuminate\Support\Collection;

// ライブのページの Summary（公式の Database とマイページで共通）。パターンを段（row）ごとに比べて、
// 差のある段は位置を合わせてまとめ、曲目は同じで順番だけ違う段は最後のパターンを使い、そのパターンを見出しにする
class SetlistSummaries
{
    /**
     * @param Collection $tourSetlists そのツアーのパターン（DbSetlist か UserSetlist）
     * @param bool $showWithoutDifferences 全パターンが同じでも Summary を出すツアー
     * @return array{setlistSummaries: Collection, summaryRows: Collection, summaryPickedPatterns: array}
     */
    public static function build(Collection $tourSetlists, $songs, bool $showWithoutDifferences = false): array
    {
        // 曲目は同じで順番だけ違う row は、全公演がその順番ではないので、Summary に使ったパターンを見出しにする（row => パターン）
        $summaryPickedPatterns = [];

        // row（同時に見比べる列同士）ごとに、パターンが2つ以上ある場合にSummaryを作る。
        $setlistSummaries = $tourSetlists
            ->groupBy(fn ($m) => $m->row ?? 1)
            ->sortKeys()
            ->map(function ($rowSetlists, $rowNum) use ($songs, $showWithoutDifferences, &$summaryPickedPatterns) {
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
                // 曲数が同じでも、入っている曲が違えば差異あり（例: 5×20 は前半が「愛を叫べ」、後半が「BRAVE」で、
                // 位置がずれるので同じ行に並ばず、曲数も同じ。順番だけの違いとみなすと最後のパターンしか出ない）
                $songSets = $rowSetlists->map(function ($s) {
                    $ids = array_map(fn ($i) => (string) ($i['song'] ?? ''), array_merge($s->setlist ?? [], $s->encore ?? []));
                    sort($ids);
                    return json_encode($ids);
                });
                $hasSongDifference = $songSets->unique()->count() > 1;
                if ($hasVariantDifference || $hasCountDifference || $hasSongDifference) {
                    return $summary;
                }

                // 曲目は同じで順番だけ違う場合も、order_noが最後のパターンをSummaryに使う
                $orders = $rowSetlists->map(fn ($s) => json_encode(array_map(fn ($i) => $i['song'] ?? null, array_merge($s->setlist ?? [], $s->encore ?? []))));
                $onlyOrderDiffers = $orders->unique()->count() > 1;
                if (!$onlyOrderDiffers && !$showWithoutDifferences) {
                    return null;
                }

                // 全パターンが同じ曲順・曲目の場合は、同じ行を再マージせず、
                // 指定公演についてはorder_noが最後のパターンをそのままSummaryに使う。
                $finalPattern = $rowSetlists->sortBy('order_no')->last();
                if ($onlyOrderDiffers) {
                    $summaryPickedPatterns[$rowNum] = $finalPattern;
                }
                return buildSetlistPatternSummary(collect([$finalPattern]), $songs);
            })
            ->filter();

        // Summaryページでは差異のあるrowだけでなく、同じ曲順のrowも含めて
        // ツアー内のすべてのrowを表示する。差異のあるrowは上で計算した結果を再利用し、
        // Summary対象外だったrowだけ単独パターンを含めて構築する。
        $summaryRows = $tourSetlists
            ->groupBy(fn ($m) => $m->row ?? 1)
            ->sortKeys()
            ->map(fn ($rowSetlists, $rowNum) => $setlistSummaries->get($rowNum) ?? buildSetlistPatternSummary($rowSetlists, $songs));

        return compact('setlistSummaries', 'summaryRows', 'summaryPickedPatterns');
    }
}

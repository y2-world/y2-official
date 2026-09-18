<?php

if (!function_exists('renderSubtitleWithGreyedVenues')) {
    // セットリストのsubtitle（例:「7.17 東京1 7.18 東京2」）から、
    // 「数字.数字」を含む日付らしいパターン（範囲・カンマ区切り可）を繰り返し検出し、
    // 日付と日付の間に挟まる部分を会場名としてグレー表示するHTMLを組み立てる。
    // db_concerts/_setlist_rows.blade.php と mypage/attendances/setlists.blade.php の
    // カード見出しの両方で使う共通ロジック。
    function renderSubtitleWithGreyedVenues(?string $subtitle): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($subtitle ?? ''));
        $lines = array_values(array_filter($lines, fn ($line) => trim($line) !== ''));

        // 末尾の「-」だけで終わる開催期間未定表記（例:「6.13-」）も日付として扱う。
        // 「9.16-12.23, 1.20-3.8」のようにカンマで複数の開催期間が並ぶ場合も
        // カンマ+空白ごと日付本体に含めて1つのまとまりとして扱う（会場名と誤認させない）
        $datePattern = '/(\d{1,2}\.\d{1,2}(?:\s*[-,]\s*\d{1,2}(?:\.\d{1,2})?)*-?)/u';

        $results = array_map(function ($line) use ($datePattern) {
            $line = trim($line);
            $segments = preg_split($datePattern, $line, -1, PREG_SPLIT_DELIM_CAPTURE);

            if (count($segments) <= 1) {
                return ['html' => e($line), 'hasVenue' => false];
            }

            $html = '';
            $hasVenue = false;
            foreach ($segments as $i => $segment) {
                if ($i % 2 === 1) {
                    // 奇数インデックス = 日付本体
                    $html .= e($segment);
                    continue;
                }

                $venue = trim($segment);
                if ($venue === '') {
                    continue;
                }

                if ($i === 0) {
                    // 最初の日付より前の文字列はラベル扱いでそのまま
                    $html .= e($venue);
                } else {
                    // 会場名は見出しサイズに対する相対値(em)。見出し自体が行数で縮小されると連動して縮む
                    $html .= '<span style="font-size: 0.5em; color: #999; font-weight: normal; margin-left: 3px;">' . e($venue) . '</span>';
                    $hasVenue = true;
                    if (isset($segments[$i + 1])) {
                        $html .= ' ';
                    }
                }
            }

            return ['html' => $html, 'hasVenue' => $hasVenue];
        }, $lines);

        $renderedLines = array_column($results, 'html');

        // 「日付+会場」がある行の数が増えるほど見出し全体（日付・会場名とも）を段階的に縮小する
        $lineCount = count(array_filter($results, fn ($r) => $r['hasVenue']));
        $fontSize = match (true) {
            $lineCount >= 4 => '0.75em',
            $lineCount >= 3 => '0.95em',
            default => null,
        };

        return [
            'lines' => $renderedLines,
            'font_size' => $fontSize,
        ];
    }
}

if (!function_exists('groupDailySongClusters')) {
    // setlist/encoreのitem配列から、日替わりの候補曲グループを順番に抽出する。
    // 登録運用上、1曲目（is_dailyなし・通常表示される曲）の直後に2曲目以降の
    // 候補（is_daily=true、"-"付きインライン表示）が続く形で日替わりが表現されるため、
    // 「is_dailyの塊」＋「その直前にある通常曲1つ」をまとめて1つの選択肢グループにする。
    // 各グループは、直前の通常曲の番号（1始まり、無ければnull）と、選択肢item一覧を持つ。
    function groupDailySongClusters(array $items): array
    {
        $clusters = [];
        $currentCluster = null;
        $number = 0;
        $pendingNormalItem = null;
        $pendingNormalNumber = null;

        foreach ($items as $item) {
            $isDaily = !empty($item['is_daily']);

            if ($isDaily) {
                if ($currentCluster === null) {
                    $currentCluster = [
                        'after_number' => $pendingNormalNumber !== null ? $pendingNormalNumber - 1 : $number,
                        'items' => [],
                    ];
                    if ($pendingNormalItem !== null) {
                        $currentCluster['items'][] = $pendingNormalItem;
                        $pendingNormalItem = null;
                        $pendingNormalNumber = null;
                    }
                }
                $currentCluster['items'][] = $item;
                continue;
            }

            if ($currentCluster !== null) {
                $clusters[] = $currentCluster;
                $currentCluster = null;
            }

            $number++;
            // この通常曲は、次にis_dailyの塊が現れたときにグループの先頭候補として使う
            $pendingNormalItem = $item;
            $pendingNormalNumber = $number;
        }

        if ($currentCluster !== null) {
            $clusters[] = $currentCluster;
        }

        return $clusters;
    }
}

if (!function_exists('countActualSongs')) {
    // setlist/encoreのitem配列から、実際に演奏される曲数を数える。medleyの曲は
    // カウントせず（直前の通常曲の一部として扱う）、is_dailyの候補グループ
    // （groupDailySongClustersと同じ単位：直前の通常曲1つ + is_dailyが連続する曲群）は
    // 1グループにつき1曲としてカウントする。
    function countActualSongs(array $items): int
    {
        $dailyClusterItemUuids = [];
        foreach (groupDailySongClusters($items) as $cluster) {
            foreach ($cluster['items'] as $clusterItem) {
                if (isset($clusterItem['_uuid'])) {
                    $dailyClusterItemUuids[$clusterItem['_uuid']] = true;
                }
            }
        }

        $count = 0;
        $countedClusterStart = false;

        foreach ($items as $item) {
            $uuid = $item['_uuid'] ?? null;
            $isInDailyCluster = $uuid !== null && isset($dailyClusterItemUuids[$uuid]);

            if ($isInDailyCluster) {
                if (!$countedClusterStart) {
                    $count++;
                    $countedClusterStart = true;
                }
                continue;
            }
            $countedClusterStart = false;

            if (!empty($item['medley'])) {
                continue;
            }

            $count++;
        }

        return $count;
    }
}

if (!function_exists('ordinal')) {
    function ordinal(int $n): string
    {
        if ($n % 100 >= 11 && $n % 100 <= 13) {
            return $n . 'th';
        }
        return match ($n % 10) {
            1 => $n . 'st',
            2 => $n . 'nd',
            3 => $n . 'rd',
            default => $n . 'th',
        };
    }
}

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

if (!function_exists('groupAllSongClusters')) {
    // groupDailySongClustersと同じ「is_dailyの塊＋その直前の通常曲1つ」を1トラックとする
    // クラスタ化ルールで、setlist/encoreの全曲をクラスタ列に変換する（日替わりが無い曲も
    // 独立した1クラスタとして含める）。buildSetlistPatternSummaryのように、パターン間で
    // 曲順を「トラック単位」で比較する必要がある場合に使う。
    function groupAllSongClusters(array $items): array
    {
        $clusters = [];
        $currentDailyCluster = null;

        foreach ($items as $item) {
            $isDaily = !empty($item['is_daily']);

            if ($isDaily) {
                if ($currentDailyCluster === null) {
                    // 直前に積んだ通常曲（最後のクラスタ）があれば、それをこの日替わり塊に合流させる
                    if (!empty($clusters) && !($clusters[count($clusters) - 1]['is_daily'] ?? false)) {
                        $currentDailyCluster = array_pop($clusters);
                        $currentDailyCluster['is_daily'] = true;
                    } else {
                        $currentDailyCluster = ['items' => [], 'is_daily' => true];
                    }
                }
                $currentDailyCluster['items'][] = $item;
                continue;
            }

            if ($currentDailyCluster !== null) {
                $clusters[] = $currentDailyCluster;
                $currentDailyCluster = null;
            }

            $clusters[] = ['items' => [$item], 'is_daily' => false];
        }

        if ($currentDailyCluster !== null) {
            $clusters[] = $currentDailyCluster;
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

if (!function_exists('isKaraokeTrack')) {
    // アルバム/シングルのtracklist内、exception表記がカラオケ・インストゥルメンタルの
    // バージョンかどうかを判定する。カラオケ曲は実演奏音源ではないため、
    // 曲詳細ページへのリンクを張らない（プレーンテキスト表示にする）。
    function isKaraokeTrack(?string $exceptionText): bool
    {
        if (!$exceptionText) {
            return false;
        }
        return (bool) preg_match('/karaoke|カラオケ|instrumental|backing track/ui', $exceptionText);
    }
}

if (!function_exists('buildSetlistPatternSummary')) {
    // 同一row内の複数パターン（$patternsは各DbSetlist/UserSetlistモデルのコレクション）を、
    // 曲順の位置ごとに見比べてSummaryを作る。曲番の単位は生の配列インデックスではなく、
    // groupAllSongClustersと同じ「is_dailyの塊＋その直前の通常曲1つ」を1トラックとして扱う
    // （is_daily候補群の分だけ配列長が伸びて、以降の位置が全パターンでズレるのを防ぐため）。
    // 各位置のクラスタを全パターン間で見比べ、曲が一致すれば1つ、異なれば重複を除いた
    // 選択肢の一覧としてまとめる（位置ベースの単純マージ。パターン間で曲数が異なる場合、
    // 短い方は該当位置にその位置の内容が無いものとして扱うため、1曲挿入・削除があると
    // それ以降の位置がズレる制限がある）。
    // 戻り値は setlist と encore それぞれについて
    // [['variants' => [['title'=>..,'song_id'=>..|null], ...]], ...] の配列。
    function buildSetlistPatternSummary($patterns, $songs): array
    {
        $extractEntry = function (array $item) use ($songs) {
            $alternativeTitle = $item['alternative_title'] ?? '';
            // is_numericだけでは、"20180908"のような数字だけの曲名（DbSongとして
            // 登録せず生文字列のまま保存された曲）を誤ってDbSong.idの参照と解釈してしまうため、
            // 実際にそのidのDbSongが存在するかどうかで判定する（_setlist_rows.blade.phpと同じ方針）。
            $songModel = is_numeric($item['song'] ?? null) ? $songs->find($item['song']) : null;
            $songId = $songModel ? $songModel->id : null;
            $title = $alternativeTitle !== ''
                ? $alternativeTitle
                : ($songModel ? $songModel->title : ($item['song'] ?? ''));
            return [
                'title' => $title,
                'song_id' => $songId,
                'key' => $songId !== null ? 'id:' . $songId : 'title:' . $title,
            ];
        };

        $buildSection = function (string $section) use ($patterns, $extractEntry): array {
            $clusterLists = $patterns->map(function ($pattern) use ($section) {
                $items = is_array($pattern->{$section} ?? null) ? $pattern->{$section} : [];
                return groupAllSongClusters($items);
            })->values();

            $maxLen = $clusterLists->map(fn ($list) => count($list))->max() ?? 0;
            $rows = [];

            for ($i = 0; $i < $maxLen; $i++) {
                $entries = [];
                $existingKeys = [];
                foreach ($clusterLists as $list) {
                    if (!isset($list[$i])) {
                        continue;
                    }
                    foreach ($list[$i]['items'] as $item) {
                        $entry = $extractEntry($item);
                        if (!in_array($entry['key'], $existingKeys)) {
                            $entries[] = $entry;
                            $existingKeys[] = $entry['key'];
                        }
                    }
                }
                if (empty($entries)) {
                    continue;
                }
                $rows[] = ['variants' => $entries];
            }

            return $rows;
        };

        return [
            'setlist' => $buildSection('setlist'),
            'encore' => $buildSection('encore'),
        ];
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

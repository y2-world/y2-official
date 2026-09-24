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

if (!function_exists('lcsAlignEntryLists')) {
    // 2つのクラスタ列（各要素はentries=[['key'=>..],...]の配列）を、共通する曲
    // （keyが交差するクラスタ）をアンカーにして最長共通部分列（LCS）でアラインメントする。
    // 標準的な動的計画法でLCSの長さテーブルを作り、そこから逆算してマッチしたペアの
    // (baseIndex, otherIndex) の組を先頭から順に返す（1対1、順序を保った対応）。
    function lcsAlignEntryLists(array $base, array $other): array
    {
        $m = count($base);
        $n = count($other);
        $matches = [];
        for ($i = 0; $i < $m; $i++) {
            $matches[$i] = [];
            $baseKeys = array_column($base[$i], 'key');
            for ($j = 0; $j < $n; $j++) {
                $otherKeys = array_column($other[$j], 'key');
                $matches[$i][$j] = (bool) array_intersect($baseKeys, $otherKeys);
            }
        }

        $dp = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));
        for ($i = $m - 1; $i >= 0; $i--) {
            for ($j = $n - 1; $j >= 0; $j--) {
                $dp[$i][$j] = $matches[$i][$j] ? $dp[$i + 1][$j + 1] + 1 : max($dp[$i + 1][$j], $dp[$i][$j + 1]);
            }
        }

        $pairs = [];
        $i = 0;
        $j = 0;
        while ($i < $m && $j < $n) {
            if ($matches[$i][$j]) {
                $pairs[] = [$i, $j];
                $i++;
                $j++;
            } elseif ($dp[$i + 1][$j] >= $dp[$i][$j + 1]) {
                $i++;
            } else {
                $j++;
            }
        }

        return $pairs;
    }
}

if (!function_exists('mergeEntriesPreservingEarliestOrder')) {
    // $variants（1行分のentry配列、参照渡し）に $newEntries をマージする。同じkeyの
    // entryが既にあれば追加しないが、新しく来たentryの方が_order（パターンの登場順
    // インデックス）が小さい場合は、既存entryの_orderだけ若い方に更新する。
    // 基準列（LCSアンカー方式で最初にvariantsへ入るパターン）由来のentryは、
    // 基準列自身の_orderを持ったまま残ってしまうため、これが無いと「本当は
    // もっと後のパターンで初出のはずの曲が、実は基準列にも同じ曲があった」
    // ケースで、_order昇順ソート時に基準列の位置に固定されて順番が狂う。
    // 同じkeyのentryが複数パターンから来た場合、それぞれの_sectionを_sectionVotes
    // （['setlist'=>件数, 'encore'=>件数]）に積み上げる。setlist由来の行なのか
    // encore由来の行なのか（本編/アンコール境界を跨いで動いた曲かどうか）を、後段で
    // 「より多くのパターンがどちらの由来だったか」で多数決判定するために必要
    // （1エントリの_sectionだけでは、マージで消えた側の由来情報が失われるため）。
    function mergeEntriesPreservingEarliestOrder(array &$variants, array $newEntries): void
    {
        foreach ($newEntries as $entry) {
            $existingIndex = null;
            foreach ($variants as $idx => $existing) {
                if ($existing['key'] === $entry['key']) {
                    $existingIndex = $idx;
                    break;
                }
            }

            if ($existingIndex === null) {
                $entry['_sectionVotes'] = [$entry['_section'] => 1];
                $entry['_sectionMaxOrder'] = [$entry['_section'] => $entry['_order']];
                $variants[] = $entry;
                continue;
            }

            $section = $entry['_section'] ?? null;
            if ($section !== null) {
                $variants[$existingIndex]['_sectionVotes'][$section] = ($variants[$existingIndex]['_sectionVotes'][$section] ?? 0) + 1;
                // どのパターンがこのkeyを「後のパターン」で採用していたかを
                // section別に記録する（1:1の頻出タイの際、より後のパターンの
                // sectionを優先するタイブレークに使う。_orderはマージ時に
                // 最も早いパターンへ上書きされてしまうため別管理が必要）。
                $variants[$existingIndex]['_sectionMaxOrder'][$section] = max(
                    $variants[$existingIndex]['_sectionMaxOrder'][$section] ?? -1,
                    $entry['_order']
                );
            }

            if (($entry['_order'] ?? PHP_INT_MAX) < ($variants[$existingIndex]['_order'] ?? PHP_INT_MAX)) {
                $variants[$existingIndex]['_order'] = $entry['_order'];
            }
        }
    }
}

if (!function_exists('mergePatternIntoBase')) {
    // $base（['variants'=>[entry,...]]の配列）に、1パターン分のクラスタ列 $clusters を
    // マージする。共通する曲（アンカー）でLCSアラインメントし、アンカー間のギャップは
    // 先頭から両者の短い方の長さだけ位置ベースでペア化する（同じ曲番の日替わり候補と
    // みなす）。片方のギャップの方が長い場合、その余りは「このパターンだけの追加曲」
    // として、base側の余りはそのまま単独行、other側の余りはbaseのギャップ直後に
    // 独立した行として挿入する。
    function mergePatternIntoBase(array $base, array $clusters): array
    {
        $baseEntryLists = array_map(fn ($row) => $row['variants'], $base);
        $pairs = lcsAlignEntryLists($baseEntryLists, $clusters);
        $pairs[] = [count($base), count($clusters)]; // 番兵

        $insertions = []; // baseの挿入位置 => [クラスタ, クラスタ, ...]
        $prevBaseIdx = -1;
        $prevOtherIdx = -1;

        foreach ($pairs as [$bi, $oi]) {
            $baseGapStart = $prevBaseIdx + 1;
            $baseGapLen = $bi - $baseGapStart;
            $otherGapStart = $prevOtherIdx + 1;
            $otherGapLen = $oi - $otherGapStart;

            // ギャップの先頭から、両者の短い方の長さだけ位置ベースでペア化する
            // （同じ曲番の日替わり候補とみなす）。片方が長い場合、その余った分は
            // 「このパターンだけの追加曲」とみなし、base側の余りはそのまま単独行、
            // other側の余りはbaseのギャップ直後に独立した行として挿入する。
            $pairLen = min($baseGapLen, $otherGapLen);
            for ($k = 0; $k < $pairLen; $k++) {
                $basePos = $baseGapStart + $k;
                $otherPos = $otherGapStart + $k;
                mergeEntriesPreservingEarliestOrder($base[$basePos]['variants'], $clusters[$otherPos]);
            }
            if ($otherGapLen > $pairLen) {
                for ($k = $pairLen; $k < $otherGapLen; $k++) {
                    $extraCluster = $clusters[$otherGapStart + $k];
                    // このクラスタの曲が、LCSでアンカーに選ばれなかっただけで実は
                    // 挿入予定位置のすぐ近く（前後2行以内）に既に存在する場合
                    // （例: 基準列と他の既マージパターンの両方にある曲が、この
                    // パターンではアンカー候補から外れてギャップに回ってしまった
                    // ケース）、新規の独立行として挿入すると同じ曲が2箇所に重複
                    // してしまう。その場合は独立行にせず、既存の行にマージする。
                    // 探索範囲を近傍に限定するのは、$base全体を無制限に探すと、
                    // たまたま同じ曲名が全く別の日替わり位置（例: 本編前半の
                    // 単独追加曲と、本編後半の日替わり候補）に存在するだけの
                    // 無関係な曲まで誤って同一視してしまうため（実際に発生した例:
                    // ある公演だけ演奏された曲Xの位置に紛れ込んだ、全く別の
                    // 日替わり候補としての同名曲）。
                    $insertPos = $baseGapStart + $baseGapLen;
                    $searchRadius = 2;
                    $existingRowIndex = null;
                    for ($rowIdx = max(0, $insertPos - $searchRadius); $rowIdx <= min(count($base) - 1, $insertPos + $searchRadius); $rowIdx++) {
                        if (array_intersect(array_column($base[$rowIdx]['variants'], 'key'), array_column($extraCluster, 'key'))) {
                            $existingRowIndex = $rowIdx;
                            break;
                        }
                    }

                    if ($existingRowIndex !== null) {
                        mergeEntriesPreservingEarliestOrder($base[$existingRowIndex]['variants'], $extraCluster);
                    } else {
                        $insertions[$baseGapStart + $baseGapLen][] = $extraCluster;
                    }
                }
            }

            if ($bi < count($base)) {
                mergeEntriesPreservingEarliestOrder($base[$bi]['variants'], $clusters[$oi]);
            }

            $prevBaseIdx = $bi;
            $prevOtherIdx = $oi;
        }

        krsort($insertions);
        foreach ($insertions as $pos => $toInsert) {
            $rows = array_map(fn ($cluster) => [
                'variants' => array_map(
                    fn ($entry) => $entry + [
                        '_sectionVotes' => [$entry['_section'] => 1],
                        '_sectionMaxOrder' => [$entry['_section'] => $entry['_order']],
                    ],
                    $cluster
                ),
            ], $toInsert);
            array_splice($base, $pos, 0, $rows);
        }

        return $base;
    }
}

if (!function_exists('buildSetlistPatternSummary')) {
    // 同一row内の複数パターン（$patternsは各DbSetlist/UserSetlistモデルのコレクション）を、
    // 曲順の位置ごとに見比べてSummaryを作る。曲番の単位は生の配列インデックスではなく、
    // groupAllSongClustersと同じ「is_dailyの塊＋その直前の通常曲1つ」を1トラックとして扱う
    // （is_daily候補群の分だけ配列長が伸びて、以降の位置が全パターンでズレるのを防ぐため）。
    // setlistとencoreは、まず独立にマージする（各セクションで曲数が全パターン一致
    // すれば単純位置マージ、不一致ならLCSアンカー方式）。setlist側だけ曲数が
    // パターンごとに違う場合でも、encore側が全パターン共通ならencore側は安全な
    // 単純マージの恩恵を受けられる（結合してから1回で判定すると、setlist側の
    // 些細な曲数差にencore側まで巻き込まれてLCSに倒れてしまうため）。
    // 独立マージした後、setlist側の最後の行とencore側の最初の行だけを対象に、
    // 同じ曲がまたがっていれば1行に統合する（本編最後の曲とアンコール1曲目の曲が
    // パターン間で入れ替わるような、本編/アンコールの境界自体がズレるケースへの対応。
    // 境界から離れた位置の同名曲は、単に別々の演奏である可能性が高いため対象にしない）。
    // 統合後、各行が実質的にsetlist由来かencore由来かを、variants内の登場パターン数の
    // 多数決（＝頻出する方）で判定し、setlist/encoreに再分割する。
    // 各位置のクラスタは全パターン間で見比べ、曲が一致すれば1つ、異なれば重複を除いた
    // 選択肢の一覧としてまとめる。
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

        // 各パターンをクラスタ列→entries列（曲名解決済み）に変換する。各entryに
        // そのパターンの登場順インデックス（_order、$patterns内での並び順＝通常は
        // order_no順）と、由来セクション（_section、'setlist' or 'encore'）を
        // 埋め込んでおく。LCSアンカー方式では基準列由来の曲が常にvariantsの先頭に
        // 入ってしまうため、_orderを使って最後にマージ結果全体を「本当の初出
        // パターン順」へ並べ替え直す必要がある。
        $toEntryClustersFor = function ($pattern, int $patternIndex, string $section) use ($extractEntry) {
            $items = is_array($pattern->{$section} ?? null) ? $pattern->{$section} : [];
            return collect(groupAllSongClusters($items))
                ->map(function ($cluster) use ($extractEntry, $patternIndex, $section) {
                    return collect($cluster['items'])
                        ->map($extractEntry)
                        ->unique('key')
                        ->map(fn ($entry) => $entry + ['_order' => $patternIndex, '_section' => $section])
                        ->values()
                        ->all();
                })
                ->values();
        };

        $setlistClusterLists = $patterns->values()->map(fn ($pattern, $i) => $toEntryClustersFor($pattern, $i, 'setlist'))->values();
        $encoreClusterLists = $patterns->values()->map(fn ($pattern, $i) => $toEntryClustersFor($pattern, $i, 'encore'))->values();

        if ($setlistClusterLists->isEmpty()) {
            return ['setlist' => [], 'encore' => []];
        }

        // クラスタ列の一覧を、曲数（クラスタ数）が全パターンで完全一致する場合は
        // 単純な位置ベースマージ、そうでない場合はLCSアンカー方式でマージする。
        // setlist単体・encore単体を独立に呼び出すことで、例えば「本編側だけ公演に
        // よって演奏曲数が違うがアンコールは全公演共通4曲」のようなケースでも、
        // アンコール側は安全な単純マージの恩恵を受けられるようにする
        // （結合列全体の曲数一致だけで判定すると、setlist側の些細な曲数差に
        // 巻き込まれてencore側までLCSに倒れてしまうため）。
        $mergeEntryLists = function ($entryLists) {
            $lengths = $entryLists->map(fn ($list) => count($list));

            if ($lengths->unique()->count() === 1) {
                // 全パターンで曲数（クラスタ数）が完全に一致する場合は、単純な位置
                // ベースマージで十分かつ最も安全（同じ2曲が順序だけ入れ替わる等の
                // ケースで、LCSアンカー方式は共通曲をアンカーと誤認して破綻するため）。
                $maxLen = $lengths->max();
                $rows = [];
                for ($i = 0; $i < $maxLen; $i++) {
                    $entries = [];
                    foreach ($entryLists as $list) {
                        foreach ($list[$i] as $entry) {
                            $existingIndex = null;
                            foreach ($entries as $idx => $existing) {
                                if ($existing['key'] === $entry['key']) {
                                    $existingIndex = $idx;
                                    break;
                                }
                            }
                            if ($existingIndex === null) {
                                $entry['_sectionVotes'] = [$entry['_section'] => 1];
                                $entry['_sectionMaxOrder'] = [$entry['_section'] => $entry['_order']];
                                $entries[] = $entry;
                            } else {
                                $section = $entry['_section'];
                                $entries[$existingIndex]['_sectionVotes'][$section] = ($entries[$existingIndex]['_sectionVotes'][$section] ?? 0) + 1;
                                $entries[$existingIndex]['_sectionMaxOrder'][$section] = max(
                                    $entries[$existingIndex]['_sectionMaxOrder'][$section] ?? -1,
                                    $entry['_order']
                                );
                            }
                        }
                    }
                    $rows[] = ['variants' => $entries];
                }
                return $rows;
            }

            // 曲数が食い違う場合は、曲数が最も多いパターンを基準列にする。他パターンは
            // 基準列と共通する曲（アンカー）でLCSアラインメントし、アンカーとアンカーの
            // 間のギャップは、両者の長さが一致する場合だけ位置ベースでペア化する
            // （=同じ曲番の日替わり候補とみなす）。長さが食い違う場合は、どちらの
            // ギャップが本当の日替わりでどちらが独立した追加なのか機械的に判別
            // できないため、無理にペアにせず両方の曲をそのまま個別の行として挿入する
            // （誤ってペアにするより安全）。基準列由来のentryが持つ_orderは、後段の
            // mergeEntriesPreservingEarliestOrderで他パターンとの重複マージ時に
            // より早いパターンのものへ更新されうるため、最終的な並び順は必ずしも
            // 基準列のパターン順にはならない（初出パターン順を優先する）。
            $baseIndex = $entryLists->keys()->sortByDesc(fn ($i) => count($entryLists[$i]))->first();
            $base = collect($entryLists[$baseIndex])
                ->map(fn ($cluster) => [
                    'variants' => array_map(
                        fn ($entry) => $entry + [
                            '_sectionVotes' => [$entry['_section'] => 1],
                            '_sectionMaxOrder' => [$entry['_section'] => $entry['_order']],
                        ],
                        $cluster
                    ),
                ])
                ->values()
                ->all();

            foreach ($entryLists as $i => $clusters) {
                if ($i === $baseIndex) {
                    continue;
                }
                $base = mergePatternIntoBase($base, is_array($clusters) ? $clusters : $clusters->all());
            }

            return $base;
        };

        $mergedSetlist = $mergeEntryLists($setlistClusterLists);
        $mergedEncore = $mergeEntryLists($encoreClusterLists);

        // setlist単体・encore単体を独立にマージしたことで、本編最後の曲とアンコール
        // 1曲目の曲が入れ替わるようなケース（本編/アンコールの境界自体がパターン間で
        // ズレる）では、同じ曲がsetlist側の最後の行とencore側の最初の行の両方に
        // 別々に現れてしまう。setlist側最後の行とencore側最初の行だけを対象に、
        // 同じkeyを持つentryがあれば1行に統合する（境界そのものから離れた位置の
        // 同名曲は、単に別々の演奏である可能性が高いため対象にしない）。
        if (!empty($mergedSetlist) && !empty($mergedEncore)) {
            $lastSetlistIdx = count($mergedSetlist) - 1;
            $lastSetlistKeys = array_column($mergedSetlist[$lastSetlistIdx]['variants'], 'key');
            $firstEncoreKeys = array_column($mergedEncore[0]['variants'], 'key');

            if (array_intersect($lastSetlistKeys, $firstEncoreKeys)) {
                mergeEntriesPreservingEarliestOrder($mergedSetlist[$lastSetlistIdx]['variants'], $mergedEncore[0]['variants']);
                array_shift($mergedEncore);
            }
        }

        $base = array_merge($mergedSetlist, $mergedEncore);

        // LCSアンカー方式では基準列由来の曲が常にvariants先頭に来てしまう
        // （基準列は最初からvariantsに入っているため）。_orderで本来の初出
        // パターン順に並べ替え直す（PHPのusortはPHP8で安定ソート。単純位置
        // マージ側は元々走査順=初出パターン順で追加しているため、並べ替えても
        // 結果は変わらない）。
        foreach ($base as &$row) {
            usort($row['variants'], fn ($a, $b) => $a['_order'] <=> $b['_order']);
        }
        unset($row);

        // 各行が実質的にsetlist由来かencore由来かを、variants内の各entryが持つ
        // _sectionVotes（同じkeyにマージされた全パターンの_section内訳）を
        // 合算して、より多くのパターンがどちらの由来だったかで判定し、setlist/
        // encoreに再分割する（1つのentryのvariants自体は1件でも、マージで
        // 消えた側の由来情報が_sectionVotesに積み上がっているため、これで
        // 判定しないと「1パターンしか無かった側」しか見えない）。頻出数が同数の
        // 場合は、_sectionMaxOrder（各由来ごとに実際にその由来だった最も後の
        // パターンの_order）を比較し、より後のパターン由来のsectionを優先する
        // （セットリストはツアーが進むほど後の公演の形に収束していく傾向がある
        // ため、1:1の場合は新しい方を採用する。単純な_orderは最も早いパターンへ
        // 上書きされてしまうため使えず、_sectionMaxOrderで別管理する必要がある）。
        // 内部用の_order・_section・_sectionVotes・_sectionMaxOrderキーはここで取り除く。
        $setlistRows = [];
        $encoreRows = [];
        foreach ($base as $row) {
            $setlistCount = 0;
            $encoreCount = 0;
            $setlistMaxOrder = -1;
            $encoreMaxOrder = -1;
            foreach ($row['variants'] as $entry) {
                $votes = $entry['_sectionVotes'] ?? [$entry['_section'] => 1];
                $setlistCount += $votes['setlist'] ?? 0;
                $encoreCount += $votes['encore'] ?? 0;
                $maxOrders = $entry['_sectionMaxOrder'] ?? [$entry['_section'] => $entry['_order']];
                $setlistMaxOrder = max($setlistMaxOrder, $maxOrders['setlist'] ?? -1);
                $encoreMaxOrder = max($encoreMaxOrder, $maxOrders['encore'] ?? -1);
            }

            if ($setlistCount === $encoreCount) {
                $section = $encoreMaxOrder > $setlistMaxOrder ? 'encore' : 'setlist';
            } else {
                $section = $encoreCount > $setlistCount ? 'encore' : 'setlist';
            }

            $cleanedRow = [
                'variants' => array_map(
                    fn ($entry) => collect($entry)->except(['_order', '_section', '_sectionVotes', '_sectionMaxOrder'])->all(),
                    $row['variants']
                ),
            ];
            if ($section === 'encore') {
                $encoreRows[] = $cleanedRow;
            } else {
                $setlistRows[] = $cleanedRow;
            }
        }

        return [
            'setlist' => $setlistRows,
            'encore' => $encoreRows,
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

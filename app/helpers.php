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
    // または同じsummary_groupを持つクラスタをアンカーにして最長共通部分列（LCS）でアラインメントする。
    // 標準的な動的計画法でLCSの長さテーブルを作り、そこから逆算してマッチしたペアの
    // (baseIndex, otherIndex) の組を先頭から順に返す（1対1、順序を保った対応）。
    function lcsAlignEntryLists(array $base, array $other): array
    {
        $m = count($base);
        $n = count($other);
        $matches = [];
        for ($i = 0; $i < $m; $i++) {
            $matches[$i] = [];
            for ($j = 0; $j < $n; $j++) {
                $matches[$i][$j] = false;
                foreach ($base[$i] as $baseEntry) {
                    foreach ($other[$j] as $otherEntry) {
                        $baseGroup = $baseEntry['summary_group'] ?? null;
                        $otherGroup = $otherEntry['summary_group'] ?? null;

                        // 同じdaily_noteは、曲が異なっていても同じ日替わり位置の
                        // アンカーとして扱う。一方、同じ曲でも両方に異なる
                        // daily_noteが付いている場合は別位置なので、曲IDだけで
                        // 同一アンカーにしない。
                        if ($baseGroup !== null && $otherGroup !== null && $baseGroup !== $otherGroup) {
                            continue;
                        }
                        if ($baseEntry['key'] === $otherEntry['key']
                            || ($baseGroup !== null && $baseGroup === $otherGroup)) {
                            $matches[$i][$j] = true;
                            break 2;
                        }
                    }
                }
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

if (!function_exists('setlistEntryMergeIdentity')) {
    // daily_noteが異なる同一曲は、同じ楽曲の別の演奏位置として保持する。
    function setlistEntryMergeIdentity(array $entry): string
    {
        return ($entry['key'] ?? '') . "\0" . (string) ($entry['summary_group'] ?? '');
    }
}

if (!function_exists('entryListsArePositionallyConsistent')) {
    // 全パターンでクラスタ数（曲数）が完全一致していても、実際には「1曲だけ
    // 別の位置に移動している」「1曲抜けて別の1曲が増えている」ようなケースでは、
    // 単純な位置ベースマージを使うと、その1箇所より後ろの行が軒並り2曲ずつ
    // 混在してしまい壊滅的な結果になる（例: tour386のSETLIST、「煌」という曲が
    // 一部パターンには存在せず、別のパターンでは先頭に入っている＝曲数は
    // どちらも19のまま。tour323のSETLIST、pattern0だけ「Venus」が1曲多く
    // 「HEAVEN」が1曲少ない）。「曲数が一致している」だけでは位置マージの
    // 安全性を保証できないため、追加で全パターンの組み合わせに対し、
    // 1) LCSアンカーで対応するペア数がクラスタ数と一致するか（＝すべての
    //    クラスタがアンカーとして両者に共通して存在するか。「煌」のように
    //    一方にしか無いクラスタがあれば、アンカー数がクラスタ数より少なくなり
    //    ここで検出できる）、2) アンカーの対応関係にズレ（オフセットの変化）が
    //    無いか、の両方を検証する。全パターンの組み合わせで両方を満たす場合のみ
    //    真に位置が対応しているとみなし、単純位置マージを安全と判定する。
    function entryListsArePositionallyConsistent($entryLists): bool
    {
        $lists = $entryLists->values()->map(fn ($list) => is_array($list) ? $list : $list->all());
        $count = $lists->count();
        if ($count < 2) {
            return true;
        }

        for ($a = 0; $a < $count; $a++) {
            for ($b = $a + 1; $b < $count; $b++) {
                $listA = $lists[$a];
                $listB = $lists[$b];
                $pairs = lcsAlignEntryLists($listA, $listB);

                // ペア化された箇所同士のオフセット（位置の対応関係）が一貫していれば
                // 安全とみなす。ペア数がクラスタ数に届かないだけ（＝LCSがアンカー化
                // できなかったクラスタが残っただけ）で不安全と決めつけない。例えば
                // tour108で「fanfare」が両パターンに存在しつつ位置だけ違う場合、LCSは
                // 前後関係の交差でfanfareをアンカーにできないが、それ以外の全クラスタは
                // 完全に同じ位置に対応しており、単純位置マージで正しく処理できる。
                // 一方、tour386/323のように本当に位置がズレているケースは、ペア化
                // できた箇所同士でもオフセットが複数種類に分かれるため、このチェックで
                // 引き続き正しく不安全と判定される。
                $offsets = [];
                foreach ($pairs as [$ai, $bi]) {
                    $offsets[$ai - $bi] = true;
                }
                if (count($offsets) > 1) {
                    return false;
                }
            }
        }

        return true;
    }
}

if (!function_exists('mergeEntriesPreservingEarliestOrder')) {
    // $variants（1行分のentry配列、参照渡し）に $newEntries をマージする。同じkeyと
    // summary_groupの
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
                if (setlistEntryMergeIdentity($existing) === setlistEntryMergeIdentity($entry)) {
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
                $variants[$existingIndex]['_clusterPosition'] = $entry['_clusterPosition'] ?? 0;
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
            // ただし、basePos側・otherPos側どちらかのクラスタの曲が、実は相手の
            // 列の「このギャップの近傍」に既に存在する場合（＝LCSでアンカーに
            // 選ばれなかっただけで、本当はどこか別の行に対応する曲）は、ここで
            // 無関係な相手側クラスタとペア化してはいけない（例: tour323で、baseの
            // ギャップに「HEAVEN」、otherのギャップに「妖」が来るケース。「妖」は
            // base自身の別の行に既に存在する共通曲で、LCSが「革命」と「妖」の
            // 前後関係が交差するため両方を同時にアンカーにできず、どちらか一方
            // だけがアンカーとして選ばれ、残りがこのギャップ処理に回ってきて
            // しまっただけ）。この探索は必ず近傍（前後2行以内）に限定する。
            // 無制限にbase全体を探すと、たまたま同じ曲名が全く別の日替わり位置に
            // 存在するだけの無関係な曲まで誤って同一視してしまう（実際に発生した
            // 例: tour330で、1曲目の日替わり候補「fighting pose」が、全く無関係な
            // 17曲目付近の日替わり候補としても登場する。1曲目のペア化を判定する
            // 際、17曲目にある「fighting pose」まで検出して誤ってペア化を拒否し、
            // 1曲目の選択肢が分裂してしまっていた）。一方、双方のクラスタの曲が
            // お互いの列の近傍のどこにも存在しない場合は、単純にこの位置の
            // 日替わり候補とみなしてペア化してよい（例: tour323のENCORE、
            // 「家族になろうよ/道標/Dear」のように、各パターン固有でお互いの列の
            // 他の位置には出てこない曲同士の対応）。
            // baseGap側の方が長い場合（otherGap側の候補数の方が少ない場合）、
            // 単純に先頭から数えてペア化すると、まだ何のアンカーとも対応して
            // いないbaseGap先頭の位置に誤ってペア化してしまうことがある（例:
            // tour195で、baseのギャップに「きみとなら, RED」の2曲、otherの
            // ギャップに「声明」の1曲だけがあるケース。「RED」は既に他パターンの
            // マージで得票済みで、本来「声明」はこの「RED」と同じ日替わり位置に
            // 対応するはずなのに、先頭の「きみとなら」と対応させてしまっていた）。
            // このケースでは、baseGap側の各位置が既に得票しているentry数の
            // 降順で対応順序を決め、最も得票が多い（＝既存の日替わり位置として
            // 確立している）位置から優先的にotherGap側とペア化する。
            $baseGapOrder = range(0, $baseGapLen - 1);
            if ($baseGapLen > $otherGapLen) {
                usort($baseGapOrder, function ($a, $b) use ($baseGapStart, $base, $clusters) {
                    // baseGap内の位置が、other列の別の位置（このギャップの外）にも
                    // 既に存在する場合、それは「このギャップの日替わり候補」ではなく
                    // 「LCSがたまたまアンカーに選べなかっただけの、本当は別の位置に
                    // 対応する曲」である可能性が高い。そのような候補を最優先で
                    // ペア化してしまうと、本来ここでペア化されるべき候補が押し出されて
                    // 独立行に分裂する（例: tour92のENCOREで、baseGapに「id:217
                    // （ヒカリノアトリエ、他パターンでは前寄りの位置にも出現する）」
                    // 「id:132（僕らの音）」があり、otherGapに「id:113（空風の帰り道）」
                    // だけがあるケース。得票数だけで見ると同点なので先頭のid:217が
                    // 優先されてしまい、本来id:132と対応すべきid:113が誤ってid:217と
                    // ペア化を試みられ、near-elsewhereチェックで弾かれた結果
                    // 独立行になってしまっていた）。そのため、得票数より先に
                    // 「other列の他の位置に存在しない」ことを優先条件にする。
                    $existsElsewhere = function ($pos) use ($baseGapStart, $base, $clusters) {
                        $keysHere = array_column($base[$baseGapStart + $pos]['variants'], 'key');
                        foreach ($clusters as $cluster) {
                            if (array_intersect(array_column($cluster, 'key'), $keysHere)) {
                                return true;
                            }
                        }
                        return false;
                    };
                    $elsewhereA = $existsElsewhere($a);
                    $elsewhereB = $existsElsewhere($b);
                    if ($elsewhereA !== $elsewhereB) {
                        return $elsewhereA <=> $elsewhereB;
                    }
                    $votesA = array_sum($base[$baseGapStart + $a]['variants'][0]['_sectionVotes'] ?? []);
                    $votesB = array_sum($base[$baseGapStart + $b]['variants'][0]['_sectionVotes'] ?? []);
                    // 得票数が同点の場合は、無理に優先順位をつけず元の並び順
                    // （先頭から）を維持する。得票が同点ということは、まだ
                    // どちらの候補も「確立した日替わり位置」と言えるほどの
                    // 差が無いということなので、無理に並べ替えると逆に不安定な
                    // 結果を生む（例: tour330で、baseGapが「友よ/化身(v:1)」
                    // 「HUMAN(v:1)」の同点2候補のとき、無理にHUMANを優先すると
                    // 1曲目候補として正しい「GAME」が2曲目のHUMANの位置に
                    // 押し込まれてしまい、1曲目の日替わり群から漏れてしまう）。
                    return $votesB <=> $votesA ?: $a <=> $b;
                });
            }

            $pairLen = min($baseGapLen, $otherGapLen);
            $unpairedOtherPositions = [];
            for ($k = 0; $k < $pairLen; $k++) {
                $basePos = $baseGapStart + $baseGapOrder[$k];
                $otherPos = $otherGapStart + $k;
                $baseKeysHere = array_column($base[$basePos]['variants'], 'key');
                $otherKeysHere = array_column($clusters[$otherPos], 'key');

                // otherGap側の曲がbase内のどこかに既に存在するかどうかは、base全体を
                // 探索する（近傍±2行に限定しない）。近傍限定だと、baseGap自体が
                // 広い（=basePosから既存の同一曲の行までの距離が2行を超える）場合に
                // 見逃してしまい、無関係な位置に誤ってペア化してしまう
                // （例: tour73で、baseの[2]に既にある「ニシエヒガシエ」が、
                // 3行離れた[5]「id:179」の位置に誤って同居させられていた）。
                $otherHasElsewhere = false;
                for ($rowIdx = 0; $rowIdx < count($base); $rowIdx++) {
                    if ($rowIdx === $basePos) {
                        continue;
                    }
                    if (array_intersect(array_column($base[$rowIdx]['variants'], 'key'), $otherKeysHere)) {
                        $otherHasElsewhere = true;
                        break;
                    }
                }

                $baseHasElsewhere = false;
                for ($clusterIdx = 0; $clusterIdx < count($clusters); $clusterIdx++) {
                    if ($clusterIdx === $otherPos) {
                        continue;
                    }
                    if (array_intersect(array_column($clusters[$clusterIdx], 'key'), $baseKeysHere)) {
                        $baseHasElsewhere = true;
                        break;
                    }
                }

                if (!$otherHasElsewhere && !$baseHasElsewhere) {
                    mergeEntriesPreservingEarliestOrder($base[$basePos]['variants'], $clusters[$otherPos]);
                } else {
                    $unpairedOtherPositions[] = $otherPos;
                }
            }
            if ($otherGapLen > $pairLen) {
                for ($k = $pairLen; $k < $otherGapLen; $k++) {
                    $unpairedOtherPositions[] = $otherGapStart + $k;
                }
            }
            if (!empty($unpairedOtherPositions)) {
                foreach ($unpairedOtherPositions as $otherPos) {
                    $extraCluster = $clusters[$otherPos];
                    // このクラスタの曲が、LCSでアンカーに選ばれなかっただけで実は
                    // base内の別の位置に既に存在する場合（例: 基準列と他の既マージ
                    // パターンの両方にある曲が、このパターンではアンカー候補から
                    // 外れてギャップに回ってしまったケース）、新規の独立行として
                    // 挿入すると同じ曲が2箇所に重複してしまう。その場合は独立行に
                    // せず、既存の行にマージする。base全体を探索するのは、挿入予定
                    // 位置から離れた行に既存の同じ曲があるケース（例: tour73で、
                    // baseの[2]/[6]に既にある「ニシエヒガシエ」「id:179」が、
                    // 挿入予定位置から3行以上離れていたため近傍±2では見つからず、
                    // 重複した独立行として挿入されてしまっていた）でも正しく統合する
                    // ため。
                    $insertPos = $baseGapStart + $baseGapLen;
                    $existingRowIndex = null;
                    for ($rowIdx = 0; $rowIdx < count($base); $rowIdx++) {
                        $existingIdentities = array_map('setlistEntryMergeIdentity', $base[$rowIdx]['variants']);
                        $extraIdentities = array_map('setlistEntryMergeIdentity', $extraCluster);
                        if (array_intersect($existingIdentities, $extraIdentities)) {
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
    // $forceSimpleEncoreMerge: trueの場合、encore側は曲数がパターン間で食い違って
    // いてもLCSアンカー方式を使わず、常に単純な位置ベースマージ（最長パターンの
    // 位置に全パターンの該当曲を集める）を使う。福山雅治のように、1つのツアーで
    // アンコールの構成が公演ごとに大きく異なり、かつ同じ曲（例: MELODY）が
    // 全公演共通のアンカーとして存在するケースでは、LCSアンカー方式がアンカー前後の
    // 曲を誤って別の日替わり位置に押し込め合ってしまい、崩壊した表示になるため。
    function buildSetlistPatternSummary($patterns, $songs, bool $forceSimpleEncoreMerge = false): array
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
            // summaryGroup: DbSetlistRow編集画面の「daily_note」欄（Summary日替わり
            // グループ）に手動で入力された値。LCSアンカー方式では機械的に判別
            // できない「本当は同じ日替わり位置の候補だが、たまたま曲順や基準
            // パターンの都合で別の行に分裂してしまう」ケース（例: tour187の
            // もうはなさない/Hi/ピエロ）に対して、人手で同じ値を入力しておくことで
            // マージ時に最優先で同じ行へ統合する。空文字はグループ指定なしとして
            // 通常のLCS判定に任せる。
            $summaryGroup = trim((string) ($item['daily_note'] ?? ''));
            return [
                'title' => $title,
                'song_id' => $songId,
                'key' => $songId !== null ? 'id:' . $songId : 'title:' . $title,
                'summary_group' => $summaryGroup !== '' ? $summaryGroup : null,
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
            $clusters = [];
            foreach (groupAllSongClusters($items) as $cluster) {
                $clusterItems = $cluster['items'];
                $hasSummaryGroups = collect($clusterItems)->contains(
                    fn ($item) => trim((string) ($item['daily_note'] ?? '')) !== ''
                );

                if (!$hasSummaryGroups) {
                    $clusters[] = $cluster;
                    continue;
                }

                // daily_noteが明示されている範囲では、その値を曲位置の定義として
                // 優先する。is_dailyが連続しているだけで1クラスタにまとめると、
                // 11曲目(group 1)と12曲目(group 2)のような隣接する別の日替わり枠が
                // 一緒になってしまう。無指定の直前曲は独立クラスタにし、同じ値の
                // daily_noteが連続する曲だけを1つの候補クラスタとして扱う。
                $pendingGroup = null;
                $pendingItems = [];
                foreach ($clusterItems as $item) {
                    $group = trim((string) ($item['daily_note'] ?? ''));
                    if ($group === '') {
                        if ($pendingItems !== []) {
                            $clusters[] = ['items' => $pendingItems, 'is_daily' => true];
                            $pendingItems = [];
                            $pendingGroup = null;
                        }
                        $clusters[] = ['items' => [$item], 'is_daily' => false];
                        continue;
                    }

                    if ($pendingItems !== [] && $group !== $pendingGroup) {
                        $clusters[] = ['items' => $pendingItems, 'is_daily' => true];
                        $pendingItems = [];
                    }
                    $pendingGroup = $group;
                    $pendingItems[] = $item;
                }
                if ($pendingItems !== []) {
                    $clusters[] = ['items' => $pendingItems, 'is_daily' => true];
                }
            }

            return collect($clusters)
                ->map(function ($cluster) use ($extractEntry, $patternIndex, $section) {
                    return collect($cluster['items'])
                        ->map($extractEntry)
                        ->unique('key')
                        ->values()
                        // _clusterPosition: このクラスタ内（=is_dailyの塊など、1トラック
                        // として扱われる範囲）での元の並び順。同じパターン由来の複数
                        // entryが、マージ時にキー一致の有無で別々のタイミングで処理
                        // されると、_orderが同点でも配列への追加順が元のクラスタ内順序と
                        // ズレてしまうため、_order同点時のタイブレークに使う。
                        ->map(fn ($entry, $clusterPosition) => $entry + ['_order' => $patternIndex, '_section' => $section, '_clusterPosition' => $clusterPosition])
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

        // 「基準パターン」＝Summary上で無番号（-）にする曲を判定する土台。
        // setlist・encoreを合わせた合計クラスタ数（曲数）で、最も曲数が多い
        // パターン（同数なら最後、最も新しいパターン）を1つだけ選び、setlist側・
        // encore側の両方でこの同じパターンを基準として使う（setlist側と
        // encore側で別々に基準を選ぶと、例えばsetlist側は「水上バスを含む
        // パターンA」、encore側は「Tomorrow never knowsを含むパターンB」を
        // それぞれ基準にしてしまい、is_extra判定にねじれが生じるため）。
        // ツアーが進むほどセットリストが最終形に収束していく傾向があるため、
        // 「基準パターンに存在しない曲」＝「一部の公演限定で挟まれた追加曲」
        // とみなせる。
        // is_extra判定のkey集合自体は、SETLIST/ENCOREそれぞれの行が実際に
        // 属するセクション単位で見る（合算した集合で見ると、例えば「終わりなき旅」が
        // 基準パターンではENCORE側にしか無いのに、SETLIST側の日替わり行での
        // is_extra判定が誤ってfalseになってしまう。ある公演では本編最後の曲、
        // 別の公演ではアンコール1曲目の曲、という本編/アンコール境界をまたぐ
        // 日替わりでこれが起きる）。基準パターン自体は1つに統一したまま
        // （そうしないとsetlist側とencore側で別々のパターンを基準にしてしまい
        // is_extra判定にねじれが生じるため）、そのパターンのsetlist側・encore側
        // それぞれのkey集合を別々に持つ。
        $totalLengths = $setlistClusterLists->map(fn ($list, $i) => count($list) + count($encoreClusterLists[$i]));
        $maxTotalLen = $totalLengths->max();
        $referenceIndex = $totalLengths->keys()->filter(fn ($i) => $totalLengths[$i] === $maxTotalLen)->last();
        $referenceKeysForSection = fn ($clusterLists) => collect($clusterLists[$referenceIndex])
            ->flatMap(fn ($cluster) => array_column($cluster, 'key'))
            ->flip();

        // クラスタ列の一覧を、曲数（クラスタ数）が全パターンで完全一致する場合は
        // 単純な位置ベースマージ、そうでない場合はLCSアンカー方式でマージする。
        // setlist単体・encore単体を独立に呼び出すことで、例えば「本編側だけ公演に
        // よって演奏曲数が違うがアンコールは全公演共通4曲」のようなケースでも、
        // アンコール側は安全な単純マージの恩恵を受けられるようにする
        // （結合列全体の曲数一致だけで判定すると、setlist側の些細な曲数差に
        // 巻き込まれてencore側までLCSに倒れてしまうため）。
        $mergeEntryLists = function ($entryLists, bool $forceSimple = false) use ($referenceKeysForSection, $referenceIndex) {
            $referenceKeys = $referenceKeysForSection($entryLists);
            $lengths = $entryLists->map(fn ($list) => count($list));
            // 曲数（クラスタ数）が全パターンで一致していても、それだけでは
            // 単純位置マージの安全性を保証できない（1曲が別の位置に移動している
            // だけで曲数は変わらないケースがあるため）。entryListsArePositionallyConsistent
            // で、全パターン間のLCSアンカー対応にズレが無いかも合わせて確認する。
            $isSimpleSafe = $lengths->unique()->count() === 1
                && entryListsArePositionallyConsistent($entryLists);

            if ($forceSimple || $isSimpleSafe) {
                // 全パターンで曲数（クラスタ数）が完全に一致し、かつ位置対応にも
                // ズレが無い場合は、単純な位置ベースマージで十分かつ最も安全
                // （同じ2曲が順序だけ入れ替わる等のケースで、LCSアンカー方式は
                // 共通曲をアンカーと誤認して破綻するため）。
                // $forceSimpleがtrueの場合は、曲数が食い違っていても強制的にこちらを
                // 使う（最長パターンの位置数に合わせ、足りないパターンはその位置に
                // 該当曲が無いものとして扱う）。
                $maxLen = $lengths->max();
                $rows = [];
                for ($i = 0; $i < $maxLen; $i++) {
                    $entries = [];
                    foreach ($entryLists as $list) {
                        // $forceSimple時は曲数がパターン間で食い違うため、短い方の
                        // パターンにはこの位置が存在しないことがある（その位置には
                        // 該当曲が無いものとして単に読み飛ばす）。
                        foreach ($list[$i] ?? [] as $entry) {
                            $existingIndex = null;
                            foreach ($entries as $idx => $existing) {
                                if (setlistEntryMergeIdentity($existing) === setlistEntryMergeIdentity($entry)) {
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
                return ['rows' => $rows, 'referenceKeys' => $referenceKeys];
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
            // 基準列は、is_extra判定用の基準（$referenceIndex：setlist+encoreの
            // 合計曲数が最多、同点なら最後のパターン）と同じものを使う。
            $baseIndex = $entryLists->has($referenceIndex) ? $referenceIndex : $entryLists->keys()->sortByDesc(fn ($i) => count($entryLists[$i]))->first();
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

            return ['rows' => $base, 'referenceKeys' => $referenceKeys];
        };

        $mergedSetlistResult = $mergeEntryLists($setlistClusterLists);
        $mergedEncoreResult = $mergeEntryLists($encoreClusterLists, $forceSimpleEncoreMerge);
        $mergedSetlist = $mergedSetlistResult['rows'];
        $mergedEncore = $mergedEncoreResult['rows'];
        $referenceKeysBySection = [
            'setlist' => $mergedSetlistResult['referenceKeys'],
            'encore' => $mergedEncoreResult['referenceKeys'],
        ];
        $base = array_merge($mergedSetlist, $mergedEncore);

        // is_common判定（その曲が全パターンで演奏されているか）に使う、keyごとの
        // 総出演パターン数を、重複削除で行が消される前のこの時点で集計しておく。
        // 後段の重複削除処理は「少ない方の出現を削除する」ため、削除後に集計すると
        // 削除された側の票が失われ、実際には全パターン共通の曲（例: tour103の
        // 「ポケット カスタネット」、本編/アンコールの境界がズレて2箇所に分かれて
        // いただけ）が誤って「一部公演限定」と判定されてしまう。
        $totalVotesByKey = [];
        foreach ($base as $row) {
            foreach ($row['variants'] as $entry) {
                $votes = $entry['_sectionVotes'] ?? [$entry['_section'] => 1];
                $key = $entry['key'];
                $totalVotesByKey[$key] = ($totalVotesByKey[$key] ?? 0) + array_sum($votes);
            }
        }

        // setlist単体・encore単体を独立にマージしたことで、本編最後の曲とアンコール
        // 側の曲が入れ替わるようなケース（本編/アンコールの境界自体がパターン間で
        // ズレる）や、本編内でも曲数が1パターンだけ多いことによる位置ズレでは、
        // 同じ曲が別々の行に重複して現れてしまう。$base全体を通して、同じkeyを
        // 持つ「単独行」（variantsが1件だけ＝他の日替わり選択肢とまだマージされて
        // いない行）が複数あれば、そのkeyを採用しているパターン数が少ない方の
        // 出現を削除する（多い方はそのまま残す）。行のvariantsが2件以上ある場合は
        // 対象外にする（例: 「どうしても君を失いたくない / 愛のままにわがままに」の
        // ように、その曲が既に別の日替わり選択肢の一部として1行にまとまっている
        // 場合、同じ曲名がアンコール側の別演奏にも単独で存在するだけで、両者は
        // 無関係な演奏である可能性が高いため統合しない）。
        // ただし、いずれかのパターンがそのkeyを1公演内で2回以上演奏している場合は
        // （例: アンコールでHEATを2回演奏した公演がある）、その2回目の演奏を
        // 別の日替わり位置での重複と誤認して消してしまわないよう、削除対象から除外する。
        $keyPerformedTwiceInAnyPattern = function (string $key) use ($patterns): bool {
            foreach ($patterns->values() as $pattern) {
                $items = array_merge(
                    is_array($pattern->setlist ?? null) ? $pattern->setlist : [],
                    is_array($pattern->encore ?? null) ? $pattern->encore : []
                );
                $count = 0;
                foreach ($items as $item) {
                    $songId = $item['song'] ?? null;
                    $itemKey = is_numeric($songId) ? 'id:' . $songId : null;
                    if ($itemKey === $key) {
                        $count++;
                    }
                }
                if ($count >= 2) {
                    return true;
                }
            }
            return false;
        };

        $rowIndexesForKey = [];
        $songKeyByMergeIdentity = [];
        foreach ($base as $rowIdx => $row) {
            if (count($row['variants']) !== 1) {
                continue;
            }
            $entry = $row['variants'][0];
            $key = $entry['key'];
            // song_idを持たない生文字列曲（key が "title:..." 形式）は、同名だが
            // 別の演奏を指す可能性を否定できないため対象外とする
            if (!str_starts_with($key, 'id:')) {
                continue;
            }
            $mergeIdentity = setlistEntryMergeIdentity($entry);
            $rowIndexesForKey[$mergeIdentity][] = $rowIdx;
            $songKeyByMergeIdentity[$mergeIdentity] = $key;
        }

        foreach ($rowIndexesForKey as $mergeIdentity => $rowIndexes) {
            if (count($rowIndexes) < 2) {
                continue;
            }

            $key = $songKeyByMergeIdentity[$mergeIdentity];

            if ($keyPerformedTwiceInAnyPattern($key)) {
                continue;
            }

            $countsByRow = [];
            foreach ($rowIndexes as $rowIdx) {
                $votes = $base[$rowIdx]['variants'][0]['_sectionVotes'] ?? [];
                $countsByRow[$rowIdx] = array_sum($votes) ?: 1;
            }

            // 同じ曲が本編とアンコールの両方に現れた場合は、出現数だけでなく
            // 基準パターンでの所属セクションを優先する。これにより基準パターンで
            // アンコールにある曲（例: JAP THE RIPPER）が、同数票の本編側に
            // 誤って残るのを防ぐ。基準パターンにその曲が片方のセクションでのみ
            // 存在する場合に限って優先し、両方/どちらにも無い場合は従来の
            // 出現数による判定を使う。
            $referenceSectionsForKey = [];
            foreach ($referenceKeysBySection as $section => $keys) {
                if ($keys->has($key)) {
                    $referenceSectionsForKey[] = $section;
                }
            }

            $keepCandidates = $rowIndexes;
            if (count($referenceSectionsForKey) === 1) {
                $referenceSection = $referenceSectionsForKey[0];
                $rowsInReferenceSection = array_values(array_filter(
                    $rowIndexes,
                    function ($rowIdx) use ($base, $referenceSection) {
                        $entry = $base[$rowIdx]['variants'][0];
                        $votes = $entry['_sectionVotes'] ?? [];
                        $setlistVotes = $votes['setlist'] ?? 0;
                        $encoreVotes = $votes['encore'] ?? 0;
                        if ($setlistVotes !== $encoreVotes) {
                            $section = $encoreVotes > $setlistVotes ? 'encore' : 'setlist';
                        } else {
                            $maxOrders = $entry['_sectionMaxOrder'] ?? [];
                            $section = ($maxOrders['encore'] ?? -1) > ($maxOrders['setlist'] ?? -1)
                                ? 'encore'
                                : 'setlist';
                        }

                        return $section === $referenceSection;
                    }
                ));

                if ($rowsInReferenceSection !== []) {
                    $keepCandidates = $rowsInReferenceSection;
                }
            }

            $candidateCounts = array_intersect_key($countsByRow, array_flip($keepCandidates));
            $maxCount = max($candidateCounts);
            $keepRowIdx = array_search($maxCount, $candidateCounts, true);

            foreach ($rowIndexes as $rowIdx) {
                if ($rowIdx === $keepRowIdx) {
                    continue;
                }
                $base[$rowIdx]['variants'] = [];
            }
        }

        $base = array_values(array_filter($base, fn ($row) => !empty($row['variants'])));

        // LCSアンカー方式では基準列由来の曲が常にvariants先頭に来てしまう
        // （基準列は最初からvariantsに入っているため）。_orderで本来の初出
        // パターン順に並べ替え直す（PHPのusortはPHP8で安定ソート。単純位置
        // マージ側は元々走査順=初出パターン順で追加しているため、並べ替えても
        // 結果は変わらない）。_orderが同点になる場合（同じパターン由来の複数
        // entryが、マージ過程でキー一致の有無により別々のタイミングで配列に
        // 追加され、追加順が元のクラスタ内順序とズレてしまったケース）は、
        // _clusterPosition（そのentryが元々属していたクラスタ内での並び順）で
        // タイブレークし、実際の演奏順を復元する。
        foreach ($base as &$row) {
            usort($row['variants'], fn ($a, $b) => $a['_order'] <=> $b['_order'] ?: ($a['_clusterPosition'] ?? 0) <=> ($b['_clusterPosition'] ?? 0));
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

            // is_common: その曲（key）自体が全パターンで演奏されているかどうか。
            // _sectionVotes（setlist側+encore側の合計）がパターン総数と一致しない
            // 場合は、一部の公演でしか演奏されていない曲ということなので、
            // 呼び出し側でその旨を視覚的に区別できるようfalseにする（例: tour82の
            // 「かぞえうた」は119でしか演奏されていないのでfalse、「End of the day」は
            // 全パターンで演奏されているのでtrue）。
            $patternCount = $patterns->count();
            $referenceKeys = $referenceKeysBySection[$section];
            $cleanedRow = [
                'variants' => array_map(
                    function ($entry) use ($patternCount, $totalVotesByKey, $referenceKeys) {
                        $totalVotes = $totalVotesByKey[$entry['key']] ?? 0;
                        $isCommon = $totalVotes >= $patternCount;
                        // is_extra: このentryが基準パターン（最後、または曲数最多の
                        // パターン）に存在しない曲かどうか。基準パターンに無い曲は
                        // 通常の曲番グループの一員として数えるべきでない「一部の
                        // 公演限定で挟まれた追加曲」とみなし、呼び出し側で無番号の
                        // 特別な行として表示できるようフラグを立てる（例: tour127の
                        // 「花の匂い」。同じ日替わり位置の他の候補は基準パターンに
                        // 存在するため通常通り曲番グループの一員として扱われるが、
                        // 花の匂いだけは基準パターンに無いため区別される）。
                        $isExtra = !$referenceKeys->has($entry['key']);
                        return collect($entry)
                            // summary_groupで別々の行を後から統合する場合にも、
                            // 初出パターン順を復元できるよう_orderと_clusterPositionは
                            // 統合が終わるまで保持する。
                            ->except(['_section', '_sectionVotes', '_sectionMaxOrder'])
                            ->put('is_common', $isCommon)
                            ->put('is_extra', $isExtra)
                            ->all();
                    },
                    $row['variants']
                ),
            ];
            if ($section === 'encore') {
                $encoreRows[] = $cleanedRow;
            } else {
                $setlistRows[] = $cleanedRow;
            }
        }

        // summary_group（DbSetlistRow編集画面の「daily_note」欄に手動入力された
        // 値）が同じentry同士を、LCSの自動判定結果に関わらず強制的に1つの行へ
        // 統合する。LCSアンカー方式では機械的に判別できない分裂ケース（例:
        // tour187の「もうはなさない/Hi/ピエロ」が基準パターンの都合で別々の
        // 行になってしまう）への、人手による最終手段の救済措置。setlist/encore
        // それぞれのセクション内でのみ統合する（本編/アンコール境界をまたいだ
        // グループ指定は現状のデータ構造では想定しない）。
        $applySummaryGroups = function (array $rows): array {
            $rowIndexesByGroup = [];
            foreach ($rows as $rowIdx => $row) {
                foreach ($row['variants'] as $entry) {
                    $group = $entry['summary_group'] ?? null;
                    if ($group === null) {
                        continue;
                    }
                    $rowIndexesByGroup[$group][] = $rowIdx;
                }
            }

            $mergeInto = [];
            foreach ($rowIndexesByGroup as $group => $rowIndexes) {
                $rowIndexes = array_values(array_unique($rowIndexes));
                if (count($rowIndexes) < 2) {
                    continue;
                }
                $targetRowIdx = min($rowIndexes);
                foreach ($rowIndexes as $rowIdx) {
                    if ($rowIdx !== $targetRowIdx) {
                        $mergeInto[$rowIdx] = $targetRowIdx;
                    }
                }
            }
            // Aさん→Bさんへの統合がさらにBさん→Cさんへの統合と連鎖する場合に
            // 備えて、最終的な統合先まで辿る（同じ行が複数のグループ指定に
            // またがって登場するケースへの保険）。
            $resolveTarget = function ($rowIdx) use (&$mergeInto, &$resolveTarget) {
                return isset($mergeInto[$rowIdx]) ? $resolveTarget($mergeInto[$rowIdx]) : $rowIdx;
            };

            foreach ($mergeInto as $rowIdx => $targetRowIdx) {
                $targetRowIdx = $resolveTarget($targetRowIdx);
                if ($targetRowIdx === $rowIdx) {
                    continue;
                }
                foreach ($rows[$rowIdx]['variants'] as $entry) {
                    $rows[$targetRowIdx]['variants'][] = $entry;
                }
                $rows[$rowIdx]['variants'] = [];
            }

            return array_values(array_filter($rows, fn ($row) => !empty($row['variants'])));
        };

        $setlistRows = $applySummaryGroups($setlistRows);
        $encoreRows = $applySummaryGroups($encoreRows);

        // summary_groupの統合で別行から候補が追加されているため、統合前の行順ではなく
        // 各候補の初出パターン順（同一パターン内は元の曲順）に並べ直す。
        $sortAndCleanSummaryRows = function (array $sectionRows): array {
            foreach ($sectionRows as &$row) {
                usort($row['variants'], fn ($a, $b) =>
                    ($a['_order'] ?? PHP_INT_MAX) <=> ($b['_order'] ?? PHP_INT_MAX)
                    ?: ($a['_clusterPosition'] ?? 0) <=> ($b['_clusterPosition'] ?? 0)
                );

                foreach ($row['variants'] as &$entry) {
                    unset($entry['_order'], $entry['_clusterPosition']);
                }
                unset($entry);
            }
            unset($row);
            return $sectionRows;
        };
        $setlistRows = $sortAndCleanSummaryRows($setlistRows);
        $encoreRows = $sortAndCleanSummaryRows($encoreRows);

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

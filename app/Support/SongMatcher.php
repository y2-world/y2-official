<?php

namespace App\Support;

// 一括入力の曲名を、登録済みの曲（[id => 曲名]）に照らし合わせる。
// 1. 大文字・小文字と前後の空白だけ違う曲 → 2. 表記ゆれ（全角・半角、アポストロフィの種類、～〜~、…、空白の有無など）だけ違う曲、の順に探し、
// どちらも1曲に決まったときだけつなぐ（同じ名前の曲が2つ以上あれば、誤ってつながないよう決めない）。
// 合わなかった曲には、似ている曲を候補として出す（つなぎはしない。ダイアログで知らせるだけ）
class SongMatcher
{
    /** @param array<int|string, string> $songs [id => 曲名] */
    public static function match(array $songs, string $title): int|string|null
    {
        $title = trim($title);
        foreach ([fn ($t) => mb_strtolower(trim($t)), [static::class, 'key']] as $normalize) {
            $wanted = $normalize($title);
            if ($wanted === '') {
                continue;
            }
            $hits = array_keys(array_filter($songs, fn ($song) => $normalize((string) $song) === $wanted));
            if (count($hits) === 1) {
                return $hits[0];
            }
            if (count($hits) > 1) {
                return null;
            }
        }

        return null;
    }

    // 表記ゆれを除いた比べるための形。SongTitleNormalizer（全角・半角、’‘、～〜、…）に加えて、空白と ・ - ( ) などの記号の有無も同じとみなす
    public static function key(string $title): string
    {
        $title = SongTitleNormalizer::normalize($title);
        $title = str_replace(['“', '”', '″', '＂'], '"', $title);

        return preg_replace('/[\s\'"~\-‐―－・･.,!?！？、。\/()（）\[\]［］【】*＊]+/u', '', $title);
    }

    // 似ている曲（候補。[id => 曲名]）。表記ゆれを除いた形どうしで、片方がもう片方を含む曲と、似ている度合いが高い曲を、似ている順に $limit 曲まで。
    // 表記ゆれを除くと同じになる曲があれば、その曲だけ（「miss you*」に「YOU」は出さない。1曲に決められなかった同じ名前の曲もここ）
    /** @param array<int|string, string> $songs */
    public static function candidates(array $songs, string $title, int $limit = 3): array
    {
        $wanted = static::key(static::withoutNumber($title));
        if ($wanted === '') {
            return [];
        }
        // 1文字（「轍」など）は、その文字で始まる曲だけ（「轍 -わだち-」）。どこかに含むだけの曲までは出さない
        $short = mb_strlen($wanted) < 2;
        $scores = [];
        foreach ($songs as $id => $song) {
            $key = static::key((string) $song);
            if ($key === '') {
                continue;
            }
            if ($short) {
                if (str_starts_with($key, $wanted)) {
                    $scores[$id] = $key === $wanted ? 101 : 80;
                }
                continue;
            }
            $percent = static::similarity($wanted, $key);
            $contains = mb_strlen($key) >= 2 && (str_contains($key, $wanted) || str_contains($wanted, $key));
            if ($key === $wanted || $contains || $percent >= 70) {
                $scores[$id] = $key === $wanted ? 101 : ($contains ? max($percent, 80) : $percent);
            }
        }
        if (in_array(101, $scores, true)) {
            $scores = array_filter($scores, fn ($score) => $score === 101);
        }
        arsort($scores);
        $result = [];
        foreach (array_keys(array_slice($scores, 0, $limit, true)) as $id) {
            $result[$id] = (string) $songs[$id];
        }

        return $result;
    }

    // 行の頭の曲番（「M10.」「En1.」「11.」）を外した曲名
    public static function withoutNumber(string $title): string
    {
        return preg_replace('/^\s*(?:m|en|ec|e)?\s*-?\s*\d{1,3}\s*[.．:：)）]\s*(?=\S)/iu', '', $title);
    }

    // 書いてあるバージョンの表記（$known：アルバムの曲目・今までの別表記）のうち、入力した表記に当たるもの。
    // 表記ゆれを除いて同じもの → 部分一致（片方がもう片方を含む。「雨のち晴れ (Remix)」→「雨のち晴れ Remix Version」）の順。部分一致が2つ以上あれば決めない。
    // 曲名と表記ゆれだけ違う表記（「轍 -わだち-」の曲目の「轍-わだち-」）と、曲名の一部を書いただけの入力（「轍」）は、バージョンではないので見ない
    /** @param string[] $known */
    public static function knownVersion(string $typed, array $known, string $songTitle): ?string
    {
        $wanted = static::key(static::withoutNumber($typed));
        $songKey = static::key($songTitle);
        if ($wanted === '' || str_contains($songKey, $wanted)) {
            return null;
        }
        $partial = [];
        foreach (array_unique(array_map('strval', $known)) as $title) {
            $key = static::key($title);
            if ($key === '' || $key === $songKey) {
                continue;
            }
            if ($key === $wanted) {
                return $title;
            }
            if (str_contains($key, $wanted) || str_contains($wanted, $key)) {
                $partial[$key] = $title;
            }
        }

        return count($partial) === 1 ? reset($partial) : null;
    }

    // 似ている度合い（%）。similar_text はバイトで比べるので、日本語は1文字を1バイトに置き換えてから比べる
    private static function similarity(string $a, string $b): float
    {
        $map = [];
        $encode = function (string $s) use (&$map): string {
            $out = '';
            foreach (mb_str_split($s) as $char) {
                $map[$char] ??= chr(count($map) % 256);
                $out .= $map[$char];
            }

            return $out;
        };
        similar_text($encode($a), $encode($b), $percent);

        return $percent;
    }
}

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

        return preg_replace('/[\s\'"~\-‐―－・･.,!?！？、。\/()（）\[\]［］【】]+/u', '', $title);
    }

    // 似ている曲（候補。[id => 曲名]）。表記ゆれを除いた形どうしで、片方がもう片方を含む曲と、似ている度合いが高い曲を、似ている順に $limit 曲まで。
    // 表記ゆれを除くと同じになる曲（1曲に決められなかった同じ名前の曲）も候補に入れる
    /** @param array<int|string, string> $songs */
    public static function candidates(array $songs, string $title, int $limit = 3): array
    {
        $wanted = static::key($title);
        if ($wanted === '') {
            return [];
        }
        // バージョン違い（「轍 (2019ver.)」「桜 -ballad version-」）は、うしろの表記を除いた形どうしが同じなら候補にする（isVersionOf）。
        // 1文字（「轍」など）は、その文字で始まる曲だけ（「轍 -わだち-」）。どこかに含むだけの曲までは出さない
        $short = mb_strlen($wanted) < 2;
        // 入力した曲名に含まれる曲は、バージョン表記を除いた部分に含まれるときだけ（「ultra soul 2011」の「2011」という曲は出さない）
        $wantedBase = static::key(static::base($title));
        $scores = [];
        foreach ($songs as $id => $song) {
            $key = static::key((string) $song);
            if ($key === '') {
                continue;
            }
            if (static::isVersionOf($title, (string) $song)) {
                $scores[$id] = 100;
                continue;
            }
            if ($short) {
                if (str_starts_with($key, $wanted)) {
                    $scores[$id] = $key === $wanted ? 101 : 80;
                }
                continue;
            }
            $percent = static::similarity($wanted, $key);
            $contains = mb_strlen($key) >= 2 && (str_contains($key, $wanted) || str_contains($wantedBase, $key));
            if (!$contains && str_contains($wanted, $key)) {
                continue;
            }
            if ($key === $wanted || $contains || $percent >= 70) {
                $scores[$id] = $key === $wanted ? 101 : ($contains ? max($percent, 80) : $percent);
            }
        }
        arsort($scores);
        $result = [];
        foreach (array_keys(array_slice($scores, 0, $limit, true)) as $id) {
            $result[$id] = (string) $songs[$id];
        }

        return $result;
    }

    // 入力した表記が、その曲のバージョン違い（「ultra soul 2011」と「ultra soul」）か。表記ゆれだけの違い・打ち間違いは違う扱い
    public static function isVersionOf(string $typed, string $songTitle): bool
    {
        $key = static::key($songTitle);
        $typedBase = static::key(static::base($typed));

        return static::key($typed) !== $key && $typedBase !== '' && in_array($typedBase, [$key, static::key(static::base($songTitle))], true);
    }

    // 書いてあるバージョンの表記（$known：アルバムの曲目・今までの別表記）のうち、入力した表記に当たるもの。
    // 表記ゆれを除いて同じもの → 部分一致（片方がもう片方を含む。「雨のち晴れ (Remix)」→「雨のち晴れ Remix Version」）の順。
    // 部分一致が2つ以上あれば決めない
    /** @param string[] $known */
    public static function knownVersion(string $typed, array $known): ?string
    {
        $wanted = static::key($typed);
        // 曲名だけの表記（「Ultra Soul」）は、どのバージョンにも含まれるので、部分一致とはみなさない
        $base = static::key(static::base($typed));
        $prefix = [];
        foreach (array_unique(array_map('strval', $known)) as $title) {
            $key = static::key($title);
            if ($key === $wanted) {
                return $title;
            }
            if (mb_strlen($key) > mb_strlen($base) && (str_contains($key, $wanted) || str_contains($wanted, $key))) {
                $prefix[$key] = $title;
            }
        }

        return count($prefix) === 1 ? reset($prefix) : null;
    }

    // うしろのバージョン表記などを除いた曲名。「(…)」「[…]」「～…～」「-…-」、「… ver.」「… version」、年（「ultra soul 2011」）を、うしろから順に外す
    private static function base(string $title): string
    {
        $title = trim(SongTitleNormalizer::normalize($title));
        $patterns = [
            '/\s*[(（\[［【][^()（）\[\]［］【】]*[)）\]］】]$/u',
            '/\s*[~～〜][^~～〜]+[~～〜]$/u',
            '/\s+[\-‐－―][^\-‐－―]+[\-‐－―]$/u',
            '/\s+\S*\s*(?:ver\.?|version)$/iu',
            '/\s+(?:19|20)\d{2}$/u',
        ];
        do {
            $before = $title;
            foreach ($patterns as $pattern) {
                $stripped = trim(preg_replace($pattern, '', $title));
                if ($stripped !== '') {
                    $title = $stripped;
                }
            }
        } while ($title !== $before);

        return $title;
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

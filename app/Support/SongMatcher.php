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

    // 表記ゆれを除いた比べるための形。SongTitleNormalizer（全角・半角、’‘、～〜、…）に加えて、空白と ・ - などの記号の有無も同じとみなす
    public static function key(string $title): string
    {
        $title = SongTitleNormalizer::normalize($title);
        $title = str_replace(['“', '”', '″', '＂'], '"', $title);

        return preg_replace('/[\s\'"~\-‐―－・･.,!?！？、。\/]+/u', '', $title);
    }

    // 似ている曲（候補。[id => 曲名]）。表記ゆれを除いた形どうしで、片方がもう片方を含む曲と、似ている度合いが高い曲を、似ている順に $limit 曲まで。
    // 表記ゆれを除くと同じになる曲（1曲に決められなかった同じ名前の曲）も候補に入れる
    /** @param array<int|string, string> $songs */
    public static function candidates(array $songs, string $title, int $limit = 3): array
    {
        $wanted = static::key($title);
        if (mb_strlen($wanted) < 2) {
            return [];
        }
        $scores = [];
        foreach ($songs as $id => $song) {
            $key = static::key((string) $song);
            if ($key === '') {
                continue;
            }
            similar_text($wanted, $key, $percent);
            $contains = mb_strlen($key) >= 2 && (str_contains($key, $wanted) || str_contains($wanted, $key));
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
}

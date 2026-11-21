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

    // 候補の曲（[id => 曲名]）。表記ゆれを除くと同じになる曲だけを $limit 曲まで（1曲に決められなかった同じ名前の曲もここ）。
    // 片方がもう片方を含む曲・文字が似ているだけの曲は候補にしない。候補が1つだと確かめずにつなぐので、
    // 「Do You Still Love Me」が「You」に、「Imagine」が「Image」につながってしまうため（2026-11-22 ユーザー）
    /** @param array<int|string, string> $songs */
    public static function candidates(array $songs, string $title, int $limit = 3): array
    {
        $wanted = static::key(static::withoutNumber($title));
        if ($wanted === '') {
            return [];
        }
        $result = [];
        foreach ($songs as $id => $song) {
            if (static::key((string) $song) === $wanted) {
                $result[$id] = (string) $song;
            }
        }

        return array_slice($result, 0, $limit, true);
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
}

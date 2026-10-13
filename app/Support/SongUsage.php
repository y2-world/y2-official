<?php

namespace App\Support;

use App\Models\DbAlbum;
use App\Models\DbSetlist;
use App\Models\DbSingle;
use App\Models\SlSetlist;

// 曲を消すと、アルバム・シングルの収録曲やセットリストに曲の番号だけが残り、画面に数字で出てしまう。
// 消す前に、その曲がどこで使われているかを調べる（JSON の中の "id": 番号 / "song": 番号 を探す）
class SongUsage
{
    // 公式の曲（DbSong）：アルバム・シングルの収録曲、Database のセットリスト
    public static function forDbSong(int $id): array
    {
        $usages = [];
        foreach (DbAlbum::whereRaw('tracklist::text ~ ?', [self::pattern('id', $id)])->orderBy('date')->get(['title']) as $album) {
            $usages[] = "アルバム「{$album->title}」";
        }
        foreach (DbSingle::whereRaw('tracklist::text ~ ?', [self::pattern('id', $id)])->orderBy('date')->get(['title']) as $single) {
            $usages[] = "シングル「{$single->title}」";
        }
        $setlists = DbSetlist::with('tour:id,title')
            ->where(fn ($q) => $q->whereRaw('setlist::text ~ ?', [self::pattern('song', $id)])->orWhereRaw('encore::text ~ ?', [self::pattern('song', $id)]))
            ->get(['id', 'tour_id']);
        foreach ($setlists->groupBy('tour_id') as $rows) {
            $usages[] = 'セットリスト「' . ($rows->first()->tour?->title ?? '?') . '」' . ($rows->count() > 1 ? "（{$rows->count()}パターン）" : '');
        }

        return $usages;
    }

    // セットリストの曲（SlSong）：Yuki のセットリスト（本編・アンコール・フェス）
    public static function forSlSong(int $id): array
    {
        $pattern = self::pattern('song', $id);
        $setlists = SlSetlist::where(function ($q) use ($pattern) {
            foreach (['setlist', 'encore', 'fes_setlist', 'fes_encore'] as $column) {
                $q->orWhereRaw("{$column}::text ~ ?", [$pattern]);
            }
        })->orderBy('date')->get(['id', 'date', 'title']);

        return $setlists->map(fn ($s) => 'セットリスト「' . trim(($s->date ? date('Y.m.d', strtotime($s->date)) . ' ' : '') . $s->title) . '」')->all();
    }

    // "key": 12 / "key": "12" に一致する（"key": 123 などには一致しない）
    private static function pattern(string $key, int $id): string
    {
        return '"' . $key . '"\s*:\s*"?' . $id . '"?\s*[,}]';
    }

    // 管理画面の削除を止めるときの文面
    public static function message(string $title, array $usages): string
    {
        $list = array_slice($usages, 0, 10);
        $more = count($usages) > 10 ? '　ほか' . (count($usages) - 10) . '件' : '';

        return "「{$title}」は次で使われているので削除できません。先にそこから外してください。\n" . implode("\n", $list) . $more;
    }
}

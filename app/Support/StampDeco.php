<?php

namespace App\Support;

use App\Models\StampColor;
use App\Models\StampMark;
use Illuminate\Support\Collection;

// スタンプ帳の枠の色とタイトル（マイページの利用者ごと。Yukiのスタンプ帳はYukiのアカウントのもの）。
// 色とタイトルはその人の全部のスタンプ帳で共通、どのスタンプをどの色にしたかはスタンプ帳（artist_ref）ごと
class StampDeco
{
    public static function colors(int $userId): Collection
    {
        return StampColor::where('external_user_id', $userId)->orderBy('sort_order')->orderBy('id')->get(['id', 'color', 'label']);
    }

    // 各スタンプに color_id を足す
    public static function withColors(Collection $stamps, int $userId, string $artistRef): Collection
    {
        $colorBySong = StampMark::where('external_user_id', $userId)->where('artist_ref', $artistRef)->pluck('stamp_color_id', 'song_id');

        return $stamps->map(fn ($stamp) => $stamp + ['color_id' => $colorBySong[$stamp['song_id']] ?? null]);
    }

    // colors: [{id?, color, label}]。送られなかった色は消す（その色のスタンプは色なしに戻る）
    public static function saveColors(int $userId, array $rows): void
    {
        $keep = [];
        foreach (array_values($rows) as $i => $row) {
            $color = !empty($row['id']) ? StampColor::where('external_user_id', $userId)->find($row['id']) : null;
            $color ??= new StampColor(['external_user_id' => $userId]);
            $color->fill(['color' => strtolower($row['color']), 'label' => trim($row['label']), 'sort_order' => $i])->save();
            $keep[] = $color->id;
        }
        StampColor::where('external_user_id', $userId)->whereNotIn('id', $keep)->delete();
    }

    // color_id が空なら色なしにする。自分の色でなければ false
    public static function saveMark(int $userId, string $artistRef, int $songId, ?int $colorId): bool
    {
        $key = ['external_user_id' => $userId, 'artist_ref' => $artistRef, 'song_id' => $songId];
        if (!$colorId) {
            StampMark::where($key)->delete();
            return true;
        }
        if (!StampColor::where('external_user_id', $userId)->whereKey($colorId)->exists()) {
            return false;
        }
        StampMark::updateOrCreate($key, ['stamp_color_id' => $colorId]);

        return true;
    }

    public static function colorRules(): array
    {
        return [
            'colors' => ['array'],
            'colors.*.id' => ['nullable', 'integer'],
            'colors.*.color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colors.*.label' => ['required', 'string', 'max:50'],
        ];
    }

    public static function markRules(): array
    {
        return ['song_id' => ['required', 'integer'], 'color_id' => ['nullable', 'integer']];
    }
}

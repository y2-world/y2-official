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

    // 各スタンプに color_id と、選んだ曲名の表記 title_key を足す。title は選んだ表記に差し替え、元の曲名は base_title に残す
    public static function withColors(Collection $stamps, int $userId, string $artistRef): Collection
    {
        $marks = StampMark::where('external_user_id', $userId)->where('artist_ref', $artistRef)->get(['song_id', 'stamp_color_id', 'title_key'])->keyBy('song_id');

        return $stamps->map(function ($stamp) use ($marks) {
            $mark = $marks[$stamp['song_id']] ?? null;
            // 選んだ表記が今は無い（アルバムの表記が変わった等）ときは、曲名のまま
            $titleKey = $mark && $mark->title_key && isset($stamp['track_titles'][$mark->title_key]) ? $mark->title_key : null;

            return array_merge($stamp, [
                'color_id' => $mark?->stamp_color_id,
                'title_key' => $titleKey,
                'base_title' => $stamp['title'],
                'title' => $titleKey ? $stamp['track_titles'][$titleKey] : $stamp['title'],
            ]);
        });
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
        $removed = StampColor::where('external_user_id', $userId)->whereNotIn('id', $keep)->pluck('id');
        // 消した色のスタンプは色なしに戻す（曲名の表記を選んでいれば、それは残す）
        StampMark::whereIn('stamp_color_id', $removed)->update(['stamp_color_id' => null]);
        StampMark::where('external_user_id', $userId)->whereNull('stamp_color_id')->whereNull('title_key')->delete();
        StampColor::whereIn('id', $removed)->delete();
    }

    // $changes に入っているもの（color_id / title_key）だけ変える。空なら色なし・曲名のまま。
    // 色も曲名も選んでいなければ消す。自分の色でなければ false
    public static function saveMark(int $userId, string $artistRef, int $songId, array $changes): bool
    {
        $key = ['external_user_id' => $userId, 'artist_ref' => $artistRef, 'song_id' => $songId];
        $values = [];
        if (array_key_exists('color_id', $changes)) {
            $colorId = $changes['color_id'] ?: null;
            if ($colorId && !StampColor::where('external_user_id', $userId)->whereKey($colorId)->exists()) {
                return false;
            }
            $values['stamp_color_id'] = $colorId;
        }
        if (array_key_exists('title_key', $changes)) {
            $values['title_key'] = $changes['title_key'] ?: null;
        }
        $mark = StampMark::firstOrNew($key);
        $mark->fill($values);
        if (!$mark->stamp_color_id && !$mark->title_key) {
            if ($mark->exists) {
                $mark->delete();
            }
            return true;
        }
        $mark->save();

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
        return ['song_id' => ['required', 'integer'], 'color_id' => ['nullable', 'integer'], 'title_key' => ['nullable', 'string', 'regex:/^(single|album-[0-9]+)$/']];
    }
}

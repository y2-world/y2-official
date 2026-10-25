<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Carbon;

// 管理画面の日付の欄。押すとそのままカレンダーが出て（filament/hooks/typed-date-picker の flatpickr）、数字でも打てる。
// 打てる形：「20261025」「2026.10.25」「2026/10/25」「2026-10-25」「2026年10月25日」。欄には「2026.10.25」で出し、保存は「2026-10-25」
class TypedDate
{
    public static function make(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->placeholder('例: 20261025')
            ->extraInputAttributes(['data-typed-date' => true, 'autocomplete' => 'off'])
            ->formatStateUsing(fn ($state) => self::display($state))
            ->dehydrateStateUsing(fn ($state) => self::parse($state))
            ->rules([fn () => function (string $attribute, $value, \Closure $fail) {
                if (filled($value) && self::parse($value) === null) {
                    $fail('日付は「20261025」か「2026.10.25」の形で入れてください。');
                }
            }]);
    }

    // 打った日付・保存してある日付を「2026-10-25」にする。読めなければ null
    public static function parse($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $value, $m)
            || preg_match('/^(\d{4})\s*[.\/\-年]\s*(\d{1,2})\s*[.\/\-月]\s*(\d{1,2})\s*日?$/u', $value, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]) : null;
        }
        // 保存してある日時（「2026-03-13T15:00:00.000000Z」など）は日本時間の日付にする
        try {
            return Carbon::parse($value)->setTimezone(config('app.timezone'))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    // 欄に出す形「2026.10.25」
    public static function display($value): ?string
    {
        $date = self::parse($value);

        return $date ? str_replace('-', '.', $date) : null;
    }
}

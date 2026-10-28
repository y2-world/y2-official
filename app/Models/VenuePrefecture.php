<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 会場の都道府県（Statistics の Map）。prefecture は都道府県コード（01〜47）、海外は overseas（country に国の名前）、決まっていなければ空
class VenuePrefecture extends Model
{
    protected $fillable = ['venue', 'prefecture', 'country'];

    public const OVERSEAS = 'overseas';

    public const NAMES = [
        '01' => '北海道', '02' => '青森県', '03' => '岩手県', '04' => '宮城県', '05' => '秋田県', '06' => '山形県', '07' => '福島県',
        '08' => '茨城県', '09' => '栃木県', '10' => '群馬県', '11' => '埼玉県', '12' => '千葉県', '13' => '東京都', '14' => '神奈川県',
        '15' => '新潟県', '16' => '富山県', '17' => '石川県', '18' => '福井県', '19' => '山梨県', '20' => '長野県', '21' => '岐阜県',
        '22' => '静岡県', '23' => '愛知県', '24' => '三重県', '25' => '滋賀県', '26' => '京都府', '27' => '大阪府', '28' => '兵庫県',
        '29' => '奈良県', '30' => '和歌山県', '31' => '鳥取県', '32' => '島根県', '33' => '岡山県', '34' => '広島県', '35' => '山口県',
        '36' => '徳島県', '37' => '香川県', '38' => '愛媛県', '39' => '高知県', '40' => '福岡県', '41' => '佐賀県', '42' => '長崎県',
        '43' => '熊本県', '44' => '大分県', '45' => '宮崎県', '46' => '鹿児島県', '47' => '沖縄県',
        'overseas' => '海外',
    ];

    // Yuki's Setlists・マイページの参加記録・マイページのライブの会場のうち、まだ表に無いものを足す（都道府県は空）
    public static function addMissingVenues(): void
    {
        $venues = \Illuminate\Support\Facades\DB::query()->fromSub(
            \Illuminate\Support\Facades\DB::table('sl_setlists')->selectRaw('TRIM(venue) AS v')
                ->union(\Illuminate\Support\Facades\DB::table('external_user_attendances')->selectRaw('TRIM(venue) AS v'))
                ->union(\Illuminate\Support\Facades\DB::table('user_concerts')->selectRaw('TRIM(venue) AS v')),
            'x'
        )->whereNotNull('v')->where('v', '!=', '')->pluck('v');
        $known = static::pluck('venue')->flip();
        $now = now();
        $rows = $venues->reject(fn ($v) => isset($known[$v]))->unique()->map(fn ($v) => ['venue' => $v, 'created_at' => $now, 'updated_at' => $now])->values()->all();
        if ($rows) static::insertOrIgnore($rows);
    }
}

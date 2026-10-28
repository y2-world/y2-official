<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 会場の都道府県（Statistics の Map で、行った会場の都道府県を染める）。会場は名前（前後の空白を除く）で引く。
// prefecture は都道府県コード（01 北海道 … 47 沖縄）、海外は overseas（country に国の名前）、決まっていなければ空。管理画面の「会場」で直す
return new class extends Migration
{
    private const PREFECTURES = [
        '01' => ['札幌ドーム'],
        '04' => ['仙台サンプラザホール', '石巻市総合体育館'],
        '08' => ['ザ・ヒロサワシティ会館', '水戸市民会館 グロービスホール', '結城市民文化センター アクロス 大ホール', '茨城県立県民文化センター'],
        '09' => ['宇都宮市文化会館 大ホール'],
        '10' => ['ヤマダグリーンドーム前橋', '高崎芸術劇場 大劇場'],
        '11' => ['ベルーナドーム', 'さいたまスーパーアリーナ', '狭山市市民会館', '狭山市市民会館　大ホール', '西武ドーム', 'サンシティ越谷市民ホール 大ホール'],
        '12' => ['KURKKU FIELDS', '市川市文化会館 大ホール', '市川市民会館 大ホール', '幕張メッセ 展示ホール', 'ららアリーナ 東京ベイ', '森のホール21 大ホール'],
        '13' => [
            'ニッショーホール', 'Billboard Live 東京', 'J:COMホール八王子', 'LINE CUBE SHIBUYA', 'NHKホール', 'SGC HALL ARIAKE',
            'コカ･コーラ SUMMER FES LIVEアリーナ (六本木ヒルズアリーナ)', 'TACHIKAWA STAGE GARDEN', 'TOYOTA ARENA TOKYO',
            'Zepp DiverCity', 'Zepp DiverCity (TOKYO)', 'Zepp Haneda', 'Zepp Haneda (TOKYO)', 'Zepp Tokyo', '中野サンプラザ',
            '京王アリーナTOKYO', '代アニLiveステーション', '代々木公園野外ステージ', 'エルフォスタアリーナ八王子', 'オリンパスホール八王子',
            '八王子 MatchVox', '六本木ヒルズアリーナ', '味の素スタジアム', '帝京大学八王子キャンパス キュリオシティホール',
            '恵比寿ザ・ガーデンホール', '新木場 Studio Coast', '日本武道館', '日比谷野外大音楽堂', '昭和女子大学 人見記念講堂',
            '有明アリーナ', '東京ドーム', '東京ガーデンシアター', '東京・J:COMホール八王子', '東京国際フォーラム ホールA',
            '武蔵野の森総合スポーツプラザ メインアリーナ', '渋谷クラブクアトロ', '渋谷 Spotify O-WEST', '渋谷WWW', '竹芝ニューピアホール',
            '表参道 GROUND', '豊洲PIT',
        ],
        '14' => [
            'Billboard Live 横浜', 'KT Zepp Yokohama', 'Kアリーナ横浜', 'ぴあアリーナMM', '日産スタジアム', '横浜アリーナ', '横浜スタジアム',
            'パシフィコ横浜 国立大ホール', 'パシフィコ横浜 国立大ホール (夜公演)', 'パシフィコ横浜 展示ホール', '神奈川県民ホール 大ホール',
        ],
        '22' => ['つま恋リゾート 彩の郷', 'つま恋リゾート彩の郷', '静岡エコパスタジアム', '静岡・静岡エコパアリーナ'],
        '23' => [
            'ナゴヤドーム', 'バンテリンドーム ナゴヤ', 'バンテリンドームナゴヤ', 'Aichi Sky Expo 多目的利用地A・野外',
            'Aichi Sky Expo (愛知県国際展示場) ホールA', 'Aichi Sky Expo(愛知県国際展示場)ホールA', 'IGアリーナ', 'Zepp Nagoya',
            '名古屋国際会議場 センチュリーホール', '愛知・名古屋国際会議場センチュリーホール', '日本ガイシホール',
            'ポートメッセなごや 第1展示館', 'ポートメッセなごや第1展示館', '豊田スタジアム',
        ],
        '27' => ['なんばHatch', '京セラドーム大阪', '大阪城ホール', 'ヤンマースタジアム長居'],
        '37' => ['あなぶきアリーナ香川'],
        '40' => ['マリンメッセ福岡', 'マリンメッセ福岡 A館', '福岡 PayPayドーム'],
        'overseas' => ['Aratani Theatre'],
    ];

    // 海外の会場の国
    private const COUNTRIES = [
        'Aratani Theatre' => 'アメリカ',
    ];

    public function up(): void
    {
        Schema::create('venue_prefectures', function (Blueprint $table) {
            $table->id();
            $table->string('venue')->unique();
            $table->string('prefecture', 10)->nullable();
            $table->string('country')->nullable();
            $table->timestamps();
        });

        $now = Carbon::now();
        foreach (self::PREFECTURES as $prefecture => $venues) {
            foreach ($venues as $venue) {
                DB::table('venue_prefectures')->insertOrIgnore([
                    'venue' => $venue, 'prefecture' => (string) $prefecture, 'country' => self::COUNTRIES[$venue] ?? null,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_prefectures');
    }
};

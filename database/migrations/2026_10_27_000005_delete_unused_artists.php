<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// 使っていないアーティストを消す。
// ・グループのメンバー（ソロ曲は 2026_10_27_000003 でグループの曲に移した）と、曲もセットリストも無い「さらさ」
// ・マイページの「動作確認用アーティスト」（ライブ・セットリスト・曲・アルバムも一緒に消える）
// id と名前が両方合い、中身が残っていないときだけ消す（曲が残っていると、曲まで一緒に消えるため）
return new class extends Migration
{
    private const ARTISTS = [
        41 => 'さらさ',
        95 => '藤ヶ谷太輔',
        96 => '玉森裕太',
        97 => '北山宏光',
        98 => '宮田俊哉',
        99 => '二階堂高嗣',
        100 => '横尾渉',
        101 => '千賀健永',
        103 => 'GAKU-MC',
        104 => 'ニセ明',
    ];

    public function up(): void
    {
        foreach (self::ARTISTS as $id => $name) {
            if (!DB::table('artists')->where('id', $id)->where('name', $name)->exists()) continue;
            $used = DB::table('sl_songs')->where('artist_id', $id)->exists()
                || DB::table('sl_setlists')->where('artist_id', $id)->exists()
                || DB::table('sl_setlists')->whereRaw(
                    "EXISTS (SELECT 1 FROM json_array_elements(COALESCE(fes_setlist, '[]'::json)) e WHERE e->>'artist' = ?)
                     OR EXISTS (SELECT 1 FROM json_array_elements(COALESCE(fes_encore, '[]'::json)) e WHERE e->>'artist' = ?)",
                    [(string) $id, (string) $id]
                )->exists()
                || DB::table('db_concerts')->where('artist_id', $id)->exists()
                || DB::table('db_songs')->where('artist_id', $id)->exists()
                || DB::table('db_albums')->where('artist_id', $id)->exists()
                || DB::table('db_singles')->where('artist_id', $id)->exists()
                || DB::table('artists')->where('band_artist_id', $id)->exists()
                || DB::table('stamp_marks')->whereIn('artist_ref', [(string) $id, 'official-' . $id])->exists();
            if ($used) continue;
            DB::table('artists')->where('id', $id)->delete();
        }

        // マイページの動作確認用アーティスト（参加記録が付いていたら消さない）
        $userArtist = DB::table('user_artists')->where('id', 101)->where('name', '動作確認用アーティスト')->first();
        if ($userArtist) {
            $concertIds = DB::table('user_concerts')->where('user_artist_id', 101)->pluck('id');
            $attended = DB::table('external_user_attendances')->whereIn('user_concert_id', $concertIds)->exists()
                || DB::table('external_user_attendances')->whereIn('user_setlist_id', DB::table('user_setlists')->whereIn('user_concert_id', $concertIds)->select('id'))->exists();
            if (!$attended) {
                DB::table('user_artists')->where('id', 101)->delete();
            }
        }
    }

    public function down(): void
    {
    }
};

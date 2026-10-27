<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// セットリスト楽曲にも、データベース楽曲と同じ「アーティスト表記名」（credit）を持たせる。
// グループのメンバーのソロ曲は、メンバーをアーティストにせず、グループの曲にしてアーティスト表記にメンバーの名前を入れる。
// 今までメンバーをアーティストにしていた曲を、グループの曲に移す（アーティストの行は消さない）
return new class extends Migration
{
    // メンバーのアーティスト（id => 名前） => グループのアーティスト（id => 名前）
    private const MOVES = [
        [[95, '藤ヶ谷太輔'], [11, 'Kis-My-Ft2']],
        [[96, '玉森裕太'], [11, 'Kis-My-Ft2']],
        [[97, '北山宏光'], [11, 'Kis-My-Ft2']],
        [[98, '宮田俊哉'], [11, 'Kis-My-Ft2']],
        [[99, '二階堂高嗣'], [11, 'Kis-My-Ft2']],
        [[100, '横尾渉'], [11, 'Kis-My-Ft2']],
        [[101, '千賀健永'], [11, 'Kis-My-Ft2']],
        [[103, 'GAKU-MC'], [19, 'ウカスカジー']],
        [[104, 'ニセ明'], [69, '星野源']],
    ];

    public function up(): void
    {
        Schema::table('sl_songs', function (Blueprint $table) {
            $table->string('credit')->nullable();
        });

        foreach (self::MOVES as [[$memberId, $memberName], [$groupId, $groupName]]) {
            // id と名前が両方合うときだけ（ローカルと本番で id がずれていたら何もしない）
            $ok = DB::table('artists')->where('id', $memberId)->where('name', $memberName)->exists()
                && DB::table('artists')->where('id', $groupId)->where('name', $groupName)->exists();
            if (!$ok) continue;
            foreach (DB::table('sl_songs')->where('artist_id', $memberId)->get() as $song) {
                // グループに同じ曲名があるときは移さない
                $clash = DB::table('sl_songs')->where('artist_id', $groupId)->whereRaw('LOWER(title) = LOWER(?)', [$song->title])->exists();
                if ($clash) continue;
                DB::table('sl_songs')->where('id', $song->id)->update(['artist_id' => $groupId, 'credit' => $memberName]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('sl_songs', function (Blueprint $table) {
            $table->dropColumn('credit');
        });
    }
};

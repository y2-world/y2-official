<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// パターンの公演日。管理画面の「スケジュールから選ぶ」で選んだ、ツアーのスケジュールの日（「2026-05-30」、同じ日に何回かあるときは「2000-04-06 1部」）の並び。
// 画面ではスケジュールから「5.30 大阪城ホール」のように出す。タイトル（subtitle）は別に要るときだけ入れる
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('db_setlists', function (Blueprint $table) {
            $table->json('schedule_dates')->nullable()->after('subtitle');
        });
        Schema::table('user_setlists', function (Blueprint $table) {
            $table->json('schedule_dates')->nullable()->after('subtitle');
        });
    }

    public function down(): void
    {
        Schema::table('db_setlists', function (Blueprint $table) {
            $table->dropColumn('schedule_dates');
        });
        Schema::table('user_setlists', function (Blueprint $table) {
            $table->dropColumn('schedule_dates');
        });
    }
};

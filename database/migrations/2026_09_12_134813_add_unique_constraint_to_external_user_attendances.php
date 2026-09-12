<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_user_attendances', function (Blueprint $table) {
            // 同じセットリストパターン＋同じ参加日の組み合わせでの重複登録を禁止する。
            // attended_dateがnull同士は別行として扱われる（MySQLのユニーク制約はNULLを重複とみなさない）ため、
            // 日付未入力の記録は複数登録できてしまうが、そもそも同一公演の判定ができないため許容する。
            $table->unique(['external_user_id', 'db_setlist_id', 'attended_date'], 'external_user_attendances_unique_per_date');
        });
    }

    public function down(): void
    {
        Schema::table('external_user_attendances', function (Blueprint $table) {
            $table->dropUnique('external_user_attendances_unique_per_date');
        });
    }
};

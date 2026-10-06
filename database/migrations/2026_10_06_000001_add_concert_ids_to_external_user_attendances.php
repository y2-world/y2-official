<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 参加予定（まだセットリストパターンを選んでいない参加記録）用に、ツアー・ライブを直接指す列を足す。
// パターンを選んだ記録は今まで通り db_setlist_id / user_setlist_id から辿る
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_user_attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('db_concert_id')->nullable()->after('user_setlist_id');
            $table->foreign('db_concert_id')->references('id')->on('db_concerts')->cascadeOnDelete();
            $table->foreignId('user_concert_id')->nullable()->after('db_concert_id')
                ->constrained('user_concerts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('external_user_attendances', function (Blueprint $table) {
            $table->dropForeign(['db_concert_id']);
            $table->dropColumn('db_concert_id');
            $table->dropConstrainedForeignId('user_concert_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 重複行を除去（IDが小さい方を残す）
        DB::statement('
            DELETE s1 FROM setlist_songs s1
            INNER JOIN setlist_songs s2
            WHERE s1.id > s2.id
              AND s1.artist_id = s2.artist_id
              AND s1.title = s2.title
        ');

        Schema::table('setlist_songs', function (Blueprint $table) {
            // 同一アーティスト+タイトルの重複登録を防止
            $table->unique(['artist_id', 'title'], 'setlist_songs_artist_title_unique');
        });
    }

    public function down(): void
    {
        Schema::table('setlist_songs', function (Blueprint $table) {
            $table->dropUnique('setlist_songs_artist_title_unique');
        });
    }
};




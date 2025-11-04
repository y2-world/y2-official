<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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




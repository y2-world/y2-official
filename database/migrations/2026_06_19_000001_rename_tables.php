<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('discos', 'official_releases');
        Schema::rename('lyrics', 'official_lyrics');
        Schema::rename('news', 'official_news');
        Schema::rename('profiles', 'official_profiles');
        Schema::rename('songs', 'db_songs');
        Schema::rename('albums', 'db_albums');
        Schema::rename('singles', 'db_singles');
        Schema::rename('tours', 'db_concerts');
        Schema::rename('tour_setlists', 'db_setlists');
        Schema::rename('setlists', 'sl_setlists');
        Schema::rename('setlist_songs', 'sl_songs');
    }

    public function down(): void
    {
        Schema::rename('official_releases', 'discos');
        Schema::rename('official_lyrics', 'lyrics');
        Schema::rename('official_news', 'news');
        Schema::rename('official_profiles', 'profiles');
        Schema::rename('db_songs', 'songs');
        Schema::rename('db_albums', 'albums');
        Schema::rename('db_singles', 'singles');
        Schema::rename('db_concerts', 'tours');
        Schema::rename('db_setlists', 'tour_setlists');
        Schema::rename('sl_setlists', 'setlists');
        Schema::rename('sl_songs', 'setlist_songs');
    }
};

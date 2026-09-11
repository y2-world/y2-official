<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sl_songs', function (Blueprint $table) {
            $table->unsignedInteger('db_song_id')->nullable()->after('artist_id');
            $table->foreign('db_song_id')->references('id')->on('db_songs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sl_songs', function (Blueprint $table) {
            $table->dropForeign(['db_song_id']);
            $table->dropColumn('db_song_id');
        });
    }
};

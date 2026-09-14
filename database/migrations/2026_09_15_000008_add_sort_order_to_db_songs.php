<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('db_songs', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->nullable()->after('artist_id');
        });

        // 既存データは今までの並び順（id昇順）をそのまま初期値にする。
        // アーティストごとに0から振り直すことで、Manage画面の並べ替えと同じ扱いにする。
        $artistIds = DB::table('db_songs')->distinct()->pluck('artist_id');
        foreach ($artistIds as $artistId) {
            $songIds = DB::table('db_songs')->where('artist_id', $artistId)->orderBy('id')->pluck('id');
            foreach ($songIds as $index => $songId) {
                DB::table('db_songs')->where('id', $songId)->update(['sort_order' => $index]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('db_songs', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};

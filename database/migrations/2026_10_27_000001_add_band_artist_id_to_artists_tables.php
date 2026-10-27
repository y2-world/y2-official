<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ソロプロジェクトのバンド。ソロプロジェクトとバンドは、データベースのセットリストでお互いの曲を選べる。
// 今までプログラムに直接書いていた「稲葉浩志 → B'z」を、この欄に移す
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->foreignId('band_artist_id')->nullable()->constrained('artists')->nullOnDelete();
        });
        Schema::table('user_artists', function (Blueprint $table) {
            $table->foreignId('band_artist_id')->nullable()->constrained('user_artists')->nullOnDelete();
        });

        $bz = DB::table('artists')->where('id', 3)->where('name', "B'z")->exists();
        if ($bz) {
            DB::table('artists')->where('id', 39)->where('name', '稲葉浩志')->update(['band_artist_id' => 3]);
        }
    }

    public function down(): void
    {
        Schema::table('user_artists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('band_artist_id');
        });
        Schema::table('artists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('band_artist_id');
        });
    }
};

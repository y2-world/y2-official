<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 曲のアーティスト表記（例：「あなたと」の「絢香 × コブクロ」）。曲名にアーティストを入れずに済むようにする。
// セットリストでは「曲名 / アーティスト」と出す（カバーと同じ形）
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('db_songs', function (Blueprint $table) {
            $table->string('credit')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('db_songs', function (Blueprint $table) {
            $table->dropColumn('credit');
        });
    }
};

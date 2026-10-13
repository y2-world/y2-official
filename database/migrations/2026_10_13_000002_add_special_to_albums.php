<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// アルバムの「企画盤」の印（ライブ盤・カバー盤・クリスマス盤など）。曲の「収録アルバム」は、ベスト盤と企画盤を除いた初出のオリジナルアルバムを基準にする
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('db_albums', function (Blueprint $table) {
            $table->smallInteger('special')->default(0)->after('mini');
        });
        Schema::table('user_albums', function (Blueprint $table) {
            $table->boolean('special')->default(false)->after('best');
        });
    }

    public function down(): void
    {
        Schema::table('db_albums', fn (Blueprint $table) => $table->dropColumn('special'));
        Schema::table('user_albums', fn (Blueprint $table) => $table->dropColumn('special'));
    }
};

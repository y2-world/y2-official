<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // オリジナルアルバム・ベスト・ミニのいずれにも当たらない企画盤（既存曲の再録音集等）を
        // 示すフラグ。trueの場合、通常のオリジナルアルバムの連番（album_id）を振らずに表示する。
        if (!Schema::hasColumn('db_albums', 'special')) {
            Schema::table('db_albums', function (Blueprint $table) {
                $table->boolean('special')->default(false)->after('mini');
            });
        }
    }

    public function down(): void
    {
        Schema::table('db_albums', function (Blueprint $table) {
            $table->dropColumn('special');
        });
    }
};

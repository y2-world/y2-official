<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('lyrics')) {
            return;
        }

        // ID 182のレコードを一時的に保存
        $lyric = DB::table('lyrics')->where('id', 182)->first();

        if ($lyric) {
            // ID 182のレコードを削除
            DB::table('lyrics')->where('id', 182)->delete();

            // ID 86としてレコードを再挿入
            $data = (array) $lyric;
            $data['id'] = 86;
            DB::table('lyrics')->insert($data);

            // オートインクリメントを適切な値に設定
            $maxId = DB::table('lyrics')->max('id');
            DB::statement("ALTER TABLE lyrics AUTO_INCREMENT = " . ($maxId + 1));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('lyrics')) {
            return;
        }

        // ID 86のレコードを一時的に保存
        $lyric = DB::table('lyrics')->where('id', 86)->first();

        if ($lyric) {
            // ID 86のレコードを削除
            DB::table('lyrics')->where('id', 86)->delete();

            // ID 182としてレコードを復元
            $data = (array) $lyric;
            $data['id'] = 182;
            DB::table('lyrics')->insert($data);

            // オートインクリメントを適切な値に設定
            $maxId = DB::table('lyrics')->max('id');
            DB::statement("ALTER TABLE lyrics AUTO_INCREMENT = " . ($maxId + 1));
        }
    }
};

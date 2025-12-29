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
        // ID 182のレコードを一時的に保存
        $lyric = DB::table('lyrics')->where('id', 182)->first();

        if ($lyric) {
            // ID 182のレコードを削除
            DB::table('lyrics')->where('id', 182)->delete();

            // ID 86としてレコードを再挿入
            DB::table('lyrics')->insert([
                'id' => 86,
                'title' => $lyric->title,
                'lyric' => $lyric->lyric,
                'created_at' => $lyric->created_at,
                'updated_at' => $lyric->updated_at,
            ]);

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
        // ID 86のレコードを一時的に保存
        $lyric = DB::table('lyrics')->where('id', 86)->first();

        if ($lyric) {
            // ID 86のレコードを削除
            DB::table('lyrics')->where('id', 86)->delete();

            // ID 182としてレコードを復元
            DB::table('lyrics')->insert([
                'id' => 182,
                'title' => $lyric->title,
                'lyric' => $lyric->lyric,
                'created_at' => $lyric->created_at,
                'updated_at' => $lyric->updated_at,
            ]);

            // オートインクリメントを適切な値に設定
            $maxId = DB::table('lyrics')->max('id');
            DB::statement("ALTER TABLE lyrics AUTO_INCREMENT = " . ($maxId + 1));
        }
    }
};

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
        Schema::table('news', function (Blueprint $table) {
            // 日付と時刻を含むdatetimeカラムを追加
            $table->dateTime('published_at')->nullable()->after('date');
        });

        // 既存の date カラムのデータを published_at にコピー（時刻は00:00:00）
        DB::statement('UPDATE news SET published_at = CONCAT(date, " 00:00:00") WHERE date IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });
    }
};

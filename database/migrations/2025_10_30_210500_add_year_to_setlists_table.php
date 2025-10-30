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
        Schema::table('setlists', function (Blueprint $table) {
            $table->integer('year')->nullable()->after('date');
        });

        // 既存データのyearを更新
        DB::statement('UPDATE setlists SET year = YEAR(date) WHERE date IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setlists', function (Blueprint $table) {
            $table->dropColumn('year');
        });
    }
};

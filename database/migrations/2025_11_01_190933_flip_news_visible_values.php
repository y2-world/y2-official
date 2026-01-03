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
        // Flip the visible values for all tables: 0 -> 1 (public), 1 -> 0 (private)
        // Only run if tables exist and have visible column
        if (Schema::hasTable('news') && Schema::hasColumn('news', 'visible')) {
            DB::statement('UPDATE news SET visible = CASE WHEN visible = 0 THEN 1 WHEN visible = 1 THEN 0 ELSE visible END');
        }
        if (Schema::hasTable('discos') && Schema::hasColumn('discos', 'visible')) {
            DB::statement('UPDATE discos SET visible = CASE WHEN visible = 0 THEN 1 WHEN visible = 1 THEN 0 ELSE visible END');
        }
        if (Schema::hasTable('artists') && Schema::hasColumn('artists', 'visible')) {
            DB::statement('UPDATE artists SET visible = CASE WHEN visible = 0 THEN 1 WHEN visible = 1 THEN 0 ELSE visible END');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Flip back: 1 -> 0, 0 -> 1
        if (Schema::hasTable('news') && Schema::hasColumn('news', 'visible')) {
            DB::statement('UPDATE news SET visible = CASE WHEN visible = 0 THEN 1 WHEN visible = 1 THEN 0 ELSE visible END');
        }
        if (Schema::hasTable('discos') && Schema::hasColumn('discos', 'visible')) {
            DB::statement('UPDATE discos SET visible = CASE WHEN visible = 0 THEN 1 WHEN visible = 1 THEN 0 ELSE visible END');
        }
        if (Schema::hasTable('artists') && Schema::hasColumn('artists', 'visible')) {
            DB::statement('UPDATE artists SET visible = CASE WHEN visible = 0 THEN 1 WHEN visible = 1 THEN 0 ELSE visible END');
        }
    }
};

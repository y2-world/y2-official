<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE sl_setlists ALTER COLUMN artist_id TYPE BIGINT USING NULLIF(artist_id, \'\')::bigint');
        } else {
            Schema::table('sl_setlists', function (Blueprint $table) {
                $table->unsignedBigInteger('artist_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE sl_setlists ALTER COLUMN artist_id TYPE VARCHAR(255) USING artist_id::varchar');
        } else {
            Schema::table('sl_setlists', function (Blueprint $table) {
                $table->string('artist_id')->nullable()->change();
            });
        }
    }
};

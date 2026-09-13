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
            DB::statement('ALTER TABLE db_setlists ALTER COLUMN tour_id TYPE BIGINT USING tour_id::bigint');
        } else {
            Schema::table('db_setlists', function (Blueprint $table) {
                $table->unsignedBigInteger('tour_id')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE db_setlists ALTER COLUMN tour_id TYPE VARCHAR(255) USING tour_id::varchar');
        } else {
            Schema::table('db_setlists', function (Blueprint $table) {
                $table->string('tour_id')->change();
            });
        }
    }
};

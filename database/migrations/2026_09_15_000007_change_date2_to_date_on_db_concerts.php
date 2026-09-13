<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // date2もdate1と同じくstring型のままだった。EXTRACT(year FROM date2)等の
        // 日付関数がPostgreSQLではvarchar列に対して使えないため、date型に変換する。
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE db_concerts ALTER COLUMN date2 TYPE DATE USING NULLIF(date2, \'\')::date');
        } else {
            Schema::table('db_concerts', function (Blueprint $table) {
                $table->date('date2')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE db_concerts ALTER COLUMN date2 TYPE VARCHAR(255) USING date2::varchar');
        } else {
            Schema::table('db_concerts', function (Blueprint $table) {
                $table->string('date2')->nullable()->change();
            });
        }
    }
};

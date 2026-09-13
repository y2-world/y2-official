<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // date1はもともとstring型で作られており、date型への変換にはPostgreSQLでは
        // 明示的なUSING句が必要（MySQLは文字列→日付の暗黙変換を許容する）。
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE db_concerts ALTER COLUMN date1 TYPE DATE USING NULLIF(date1, \'\')::date');
            DB::statement('ALTER TABLE db_concerts ALTER COLUMN date1 DROP NOT NULL');
        } else {
            Schema::table('db_concerts', function (Blueprint $table) {
                $table->date('date1')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE db_concerts ALTER COLUMN date1 SET NOT NULL');
        } else {
            Schema::table('db_concerts', function (Blueprint $table) {
                $table->date('date1')->nullable(false)->change();
            });
        }
    }
};

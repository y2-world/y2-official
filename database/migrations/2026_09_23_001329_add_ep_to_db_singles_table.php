<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('db_singles', 'ep')) {
            Schema::table('db_singles', function (Blueprint $table) {
                $table->boolean('ep')->default(false)->after('download');
            });
        }
    }

    public function down(): void
    {
        Schema::table('db_singles', function (Blueprint $table) {
            $table->dropColumn('ep');
        });
    }
};

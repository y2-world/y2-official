<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('db_albums', 'special')) {
            Schema::table('db_albums', function (Blueprint $table) {
                $table->dropColumn('special');
            });
        }
    }

    public function down(): void
    {
        Schema::table('db_albums', function (Blueprint $table) {
            $table->boolean('special')->default(false)->after('mini');
        });
    }
};

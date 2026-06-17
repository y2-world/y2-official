<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('albums', 'mini')) {
            Schema::table('albums', function (Blueprint $table) {
                $table->tinyInteger('mini')->default(0)->after('best');
            });
        }
    }

    public function down(): void
    {
        Schema::table('albums', function (Blueprint $table) {
            $table->dropColumn('mini');
        });
    }
};

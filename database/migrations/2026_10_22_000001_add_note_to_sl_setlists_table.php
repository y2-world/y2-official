<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 備考（同じ日の 1部・2部 など）。スケジュールから公演を選ぶと、その行のかっこ書き（「1部」）が入る
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sl_setlists', function (Blueprint $table) {
            $table->string('note')->nullable()->after('venue');
        });
    }

    public function down(): void
    {
        Schema::table('sl_setlists', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};

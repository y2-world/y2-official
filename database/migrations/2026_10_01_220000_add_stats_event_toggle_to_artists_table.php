<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Databaseのstatsで「イベントを含める」を出すか（オンのアーティストは、最初はフェス・イベントを除いて数える）。
// フェス・イベントの数がツアーよりずっと多いスキマスイッチ・Official髭男dismはオンで始める
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->boolean('stats_event_toggle')->default(false)->after('visible');
        });
        DB::table('artists')->whereIn('id', [10, 27])->update(['stats_event_toggle' => true]);
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropColumn('stats_event_toggle');
        });
    }
};

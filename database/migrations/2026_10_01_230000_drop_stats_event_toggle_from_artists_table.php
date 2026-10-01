<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 「イベントを含める」の設定をやめる（イベントを含めても順位はほとんど変わらないので、どのアーティストもイベント込みで数える）
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropColumn('stats_event_toggle');
        });
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->boolean('stats_event_toggle')->default(false)->after('visible');
        });
        DB::table('artists')->whereIn('id', [10, 27])->update(['stats_event_toggle' => true]);
    }
};

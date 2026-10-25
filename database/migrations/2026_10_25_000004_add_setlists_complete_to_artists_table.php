<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 公式のアーティストにも「全セットリスト登録済」（マイページの user_artists と同じ）。印があるときだけスタンプ帳に未演奏の背景を出す。
// 今までの公式のアーティストは、印が無くても未演奏の背景を出していたので、今あるアーティストは印ありにして見え方を変えない
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->boolean('setlists_complete')->default(false);
        });
        DB::table('artists')->update(['setlists_complete' => true]);
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropColumn('setlists_complete');
        });
    }
};

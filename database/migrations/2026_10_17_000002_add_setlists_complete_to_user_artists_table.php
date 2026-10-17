<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 「セトリを全部登録した」印。登録の途中だと、まだ登録していないライブで演奏した曲も
// 未演奏に見えてしまうので、印があるアーティストだけスタンプ帳に未演奏の背景を出す
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_artists', function (Blueprint $table) {
            $table->boolean('setlists_complete')->default(false)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('user_artists', function (Blueprint $table) {
            $table->dropColumn('setlists_complete');
        });
    }
};

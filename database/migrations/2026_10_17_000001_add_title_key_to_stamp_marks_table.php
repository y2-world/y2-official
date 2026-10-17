<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// スタンプ（曲）ごとに、どの表記の曲名で出すか（"single" / "album-{id}"。空なら曲名のまま）。
// 色を付けずに曲名だけ選ぶこともあるので、色は空でもよくする
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stamp_marks', function (Blueprint $table) {
            $table->unsignedBigInteger('stamp_color_id')->nullable()->change();
            $table->string('title_key', 40)->nullable()->after('stamp_color_id');
        });
    }

    public function down(): void
    {
        Schema::table('stamp_marks', function (Blueprint $table) {
            $table->dropColumn('title_key');
        });
    }
};

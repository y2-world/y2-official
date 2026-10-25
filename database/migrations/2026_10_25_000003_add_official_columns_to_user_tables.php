<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// マイページのテーブルを公式のテーブルと同じ作りにする（管理画面は同じ画面で、読み書きするテーブルだけを入れ替えるため）
return new class extends Migration
{
    public function up(): void
    {
        // 公開。新しく作るアーティストはオフ（本人にだけ見える）。今あるアーティストは今までどおりほかの人にも見えるよう、公開にする
        Schema::table('user_artists', function (Blueprint $table) {
            $table->smallInteger('visible')->default(0);
        });
        DB::table('user_artists')->update(['visible' => 1]);
        Schema::table('user_concerts', function (Blueprint $table) {
            $table->text('venue')->nullable();
            $table->text('text')->nullable();
        });
        Schema::table('user_songs', function (Blueprint $table) {
            $table->unsignedBigInteger('album_id')->nullable();
            $table->unsignedBigInteger('single_id')->nullable();
            $table->string('year')->nullable();
            $table->text('text')->nullable();
            $table->string('credit')->nullable();
        });
        Schema::table('user_singles', function (Blueprint $table) {
            $table->string('single_id')->nullable();
            $table->smallInteger('download')->default(0);
            $table->text('text')->nullable();
        });
        Schema::table('user_albums', function (Blueprint $table) {
            $table->string('album_id')->nullable();
            $table->text('text')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_artists', fn (Blueprint $table) => $table->dropColumn('visible'));
        Schema::table('user_concerts', fn (Blueprint $table) => $table->dropColumn(['venue', 'text']));
        Schema::table('user_songs', fn (Blueprint $table) => $table->dropColumn(['album_id', 'single_id', 'year', 'text', 'credit']));
        Schema::table('user_singles', fn (Blueprint $table) => $table->dropColumn(['single_id', 'download', 'text']));
        Schema::table('user_albums', fn (Blueprint $table) => $table->dropColumn(['album_id', 'text']));
    }
};

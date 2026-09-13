<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // My Pageでユーザーが追加したアーティスト（公式のartistsテーブルとは完全に分離）。
        // 登録した本人にしか見えない・選べない。
        Schema::create('user_artists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_user_id')->constrained('external_users')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['external_user_id', 'name']);
        });

        // ユーザーが追加したツアー・ライブ（公式のdb_concertsとは完全に分離）。
        // 常にuser_artists配下（公式アーティストへのユーザーによるツアー追加は不可）。
        Schema::create('user_concerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_user_id')->constrained('external_users')->cascadeOnDelete();
            $table->foreignId('user_artist_id')->constrained('user_artists')->cascadeOnDelete();
            $table->string('title');
            $table->integer('type')->default(1);
            $table->date('date1')->nullable();
            $table->date('date2')->nullable();
            $table->timestamps();
        });

        // ユーザーが追加したセットリストパターン（公式のdb_setlistsとは完全に分離）。
        Schema::create('user_setlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_concert_id')->constrained('user_concerts')->cascadeOnDelete();
            $table->integer('order_no')->default(1);
            $table->integer('row')->default(1);
            $table->string('subtitle')->nullable();
            $table->json('setlist')->nullable();
            $table->json('encore')->nullable();
            $table->timestamps();
        });

        // ユーザーが追加したアーティストの曲名（公式のdb_songsとは完全に分離）。
        // user_setlists.setlist/encoreのsongはこのテーブルのidを指す。
        Schema::create('user_songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_artist_id')->constrained('user_artists')->cascadeOnDelete();
            $table->string('title');
            $table->timestamps();

            $table->unique(['user_artist_id', 'title']);
        });

        // 出席記録は公式セトリ（db_setlist_id）とユーザー登録セトリ（user_setlist_id）の
        // どちらか一方だけを参照する。
        Schema::table('external_user_attendances', function (Blueprint $table) {
            $table->foreignId('user_setlist_id')->nullable()->after('db_setlist_id')
                ->constrained('user_setlists')->cascadeOnDelete();
            $table->unsignedInteger('db_setlist_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_user_attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_setlist_id');
            $table->unsignedInteger('db_setlist_id')->nullable(false)->change();
        });

        Schema::dropIfExists('user_songs');
        Schema::dropIfExists('user_setlists');
        Schema::dropIfExists('user_concerts');
        Schema::dropIfExists('user_artists');
    }
};

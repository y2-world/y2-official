<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// マイページで作ったアーティスト（user_artists）のシングル・アルバム。公式（db_singles / db_albums）と同じく、
// 収録曲は tracklist（[{"id": user_songs.id, "exception": 表記違い, "disc": ディスク番号（アルバムのみ）}]）で持つ
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_singles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_artist_id')->constrained('user_artists')->cascadeOnDelete();
            $table->foreignId('external_user_id')->constrained('external_users')->cascadeOnDelete();
            $table->string('title');
            $table->date('date')->nullable();
            $table->boolean('ep')->default(false);
            $table->json('tracklist')->nullable();
            $table->timestamps();
        });

        Schema::create('user_albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_artist_id')->constrained('user_artists')->cascadeOnDelete();
            $table->foreignId('external_user_id')->constrained('external_users')->cascadeOnDelete();
            $table->string('title');
            $table->date('date')->nullable();
            $table->boolean('mini')->default(false);
            $table->boolean('best')->default(false);
            $table->json('tracklist')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_albums');
        Schema::dropIfExists('user_singles');
    }
};

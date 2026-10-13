<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// マイページのスタンプ帳の飾り。ユーザーが「色とタイトル」を自分で決め（stamp_colors。全部のスタンプ帳で共通。
// タイトルは S・A でも「今日のプレイリスト」でも自由）、スタンプ帳ごとにスタンプ（曲）の色を選ぶ（stamp_marks）。
// artist_ref は "official-{id}" / "user-{id}"
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stamp_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_user_id')->constrained('external_users')->cascadeOnDelete();
            $table->string('color', 7);
            $table->string('label', 50);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('external_user_id');
        });

        Schema::create('stamp_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_user_id')->constrained('external_users')->cascadeOnDelete();
            $table->string('artist_ref', 40);
            $table->unsignedBigInteger('song_id');
            $table->foreignId('stamp_color_id')->constrained('stamp_colors')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['external_user_id', 'artist_ref', 'song_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stamp_marks');
        Schema::dropIfExists('stamp_colors');
    }
};

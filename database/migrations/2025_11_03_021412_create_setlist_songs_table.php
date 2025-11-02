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
        Schema::create('setlist_songs', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // 曲名
            $table->foreignId('artist_id')->nullable()->constrained('artists')->onDelete('cascade'); // アーティストID
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setlist_songs');
    }
};

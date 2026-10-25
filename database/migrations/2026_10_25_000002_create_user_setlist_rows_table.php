<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// マイページのツアーの段のグループタイトル（公式の db_setlist_rows と同じ作り）
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_setlist_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_concert_id')->constrained('user_concerts')->cascadeOnDelete();
            $table->unsignedTinyInteger('row')->default(1);
            $table->unsignedInteger('order_no')->default(1);
            $table->string('title');
            $table->timestamps();

            $table->unique(['user_concert_id', 'row', 'order_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_setlist_rows');
    }
};

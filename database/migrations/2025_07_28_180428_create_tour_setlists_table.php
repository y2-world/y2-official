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
        Schema::create('tour_setlists', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('tour_id'); // varchar(255) - tourのIDを文字列で保持
            $table->integer('order_no')->nullable();
            $table->string('subtitle')->nullable();
            $table->json('setlist')->nullable();
            $table->json('encore')->nullable();
            $table->timestamps();

            // ユニークキー: tour_idとorder_noの組み合わせ
            $table->unique(['tour_id', 'order_no'], 'idx_tour_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_setlists');
    }
};

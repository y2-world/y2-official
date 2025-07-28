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
            $table->id();
            $table->unsignedInteger('tour_id');
            $table->integer('order_no')->nullable();
            $table->date('date1')->nullable();
            $table->date('date2')->nullable();
            $table->string('subtitle')->nullable();
            $table->json('setlist')->nullable();
            $table->json('encore')->nullable();
            $table->timestamps();

            $table->foreign('tour_id')->references('id')->on('tours')->onDelete('cascade');
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

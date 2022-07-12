<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Setlists extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setlists', function (Blueprint $table) {
            $table->id();
            $table->string('artist_id');
            $table->string('tour_title');
            $table->date('date');
            $table->integer('year')->nullable();
            $table->string('venue');
            $table->json('setlist')->nullable();
            $table->json('encore')->nullable();
            $table->integer('fes')->nullable();
            $table->json('fes_setlist')->nullable();
            $table->json('fes_encore')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('setlists');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateToursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->integer('tour_id')->nullable();
            $table->integer('event_id')->nullable();
            $table->integer('ap_id')->nullable();
            $table->string('title');
            $table->integer('type');
            $table->date('date1');
            $table->date('date2')->nullable();
            $table->json('year')->nullable();
            $table->text('setlist1')->nullable();
            $table->text('setlist2')->nullable();
            $table->text('schedule')->nullable();
            $table->text('text')->nullable();
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
        Schema::dropIfExists('tours');
    }
}

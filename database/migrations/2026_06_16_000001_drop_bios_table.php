<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('bios');
    }

    public function down(): void
    {
        Schema::create('bios', function ($table) {
            $table->id();
            $table->unsignedBigInteger('artist_id')->nullable();
            $table->integer('year');
            $table->text('text')->nullable();
            $table->timestamps();
        });
    }
};

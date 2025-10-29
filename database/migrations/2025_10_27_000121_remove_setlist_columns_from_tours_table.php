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
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['setlist1', 'setlist2', 'setlist3', 'setlist4', 'setlist5', 'setlist6']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->json('setlist1')->nullable();
            $table->json('setlist2')->nullable();
            $table->json('setlist3')->nullable();
            $table->json('setlist4')->nullable();
            $table->json('setlist5')->nullable();
            $table->json('setlist6')->nullable();
        });
    }
};

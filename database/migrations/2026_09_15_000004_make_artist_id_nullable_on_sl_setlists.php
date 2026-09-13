<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sl_setlists', function (Blueprint $table) {
            $table->string('artist_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sl_setlists', function (Blueprint $table) {
            $table->string('artist_id')->nullable(false)->change();
        });
    }
};

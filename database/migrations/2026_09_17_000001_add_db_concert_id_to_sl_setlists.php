<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sl_setlists', function (Blueprint $table) {
            $table->unsignedBigInteger('db_concert_id')->nullable()->after('artist_id');
        });
    }

    public function down(): void
    {
        Schema::table('sl_setlists', function (Blueprint $table) {
            $table->dropColumn('db_concert_id');
        });
    }
};

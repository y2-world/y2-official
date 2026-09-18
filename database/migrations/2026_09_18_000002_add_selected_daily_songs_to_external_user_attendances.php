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
        Schema::table('external_user_attendances', function (Blueprint $table) {
            $table->json('selected_daily_songs')->nullable()->after('venue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_user_attendances', function (Blueprint $table) {
            $table->dropColumn('selected_daily_songs');
        });
    }
};

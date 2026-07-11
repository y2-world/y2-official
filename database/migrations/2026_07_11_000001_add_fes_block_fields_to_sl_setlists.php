<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sl_setlists', function (Blueprint $table) {
            $table->string('fes_type')->nullable()->default('interleaved')->after('fes');
            $table->json('fes_blocks')->nullable()->after('fes_encore');
            $table->json('fes_blocks_encore')->nullable()->after('fes_blocks');
        });
    }

    public function down(): void
    {
        Schema::table('sl_setlists', function (Blueprint $table) {
            $table->dropColumn(['fes_type', 'fes_blocks', 'fes_blocks_encore']);
        });
    }
};

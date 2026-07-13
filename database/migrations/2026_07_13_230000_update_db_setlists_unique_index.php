<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('db_setlists', function (Blueprint $table) {
            $table->dropUnique('idx_tour_order');
            $table->unique(['tour_id', 'order_no', 'row'], 'idx_tour_order_row');
        });
    }

    public function down(): void
    {
        Schema::table('db_setlists', function (Blueprint $table) {
            $table->dropUnique('idx_tour_order_row');
            $table->unique(['tour_id', 'order_no'], 'idx_tour_order');
        });
    }
};

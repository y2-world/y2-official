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
        Schema::table('db_setlists', function (Blueprint $table) {
            $table->unsignedTinyInteger('row')->default(1)->after('order_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('db_setlists', function (Blueprint $table) {
            $table->dropColumn('row');
        });
    }
};

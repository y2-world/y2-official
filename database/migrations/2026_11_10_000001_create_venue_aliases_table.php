<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 会場の別名（改称する前の名前・書き方の違い）=> 今の会場名。Database の会場のページ・会場検索・stats の Top Venues で、同じ会場として数える。
// 建て替えた会場は別の会場なので入れない。管理画面の「会場の別名」で足す
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('alias')->unique();
            $table->string('venue');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_aliases');
    }
};

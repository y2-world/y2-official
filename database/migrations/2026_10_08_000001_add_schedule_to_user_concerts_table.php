<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// マイページで作ったツアーにも、公式（db_concerts.schedule）と同じ日程表を持たせる。
// 参加登録の参加日を、日程表の公演から選べるようにするため
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_concerts', function (Blueprint $table) {
            $table->text('schedule')->nullable()->after('date2');
        });
    }

    public function down(): void
    {
        Schema::table('user_concerts', function (Blueprint $table) {
            $table->dropColumn('schedule');
        });
    }
};

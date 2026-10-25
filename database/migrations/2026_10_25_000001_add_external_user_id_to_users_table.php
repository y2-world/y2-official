<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 管理画面（/admin）にマイページの人も入れるようにする。マイページのアカウントで /admin にログインすると、
// そのアカウントに結び付いた users の行（管理画面用）を作って使う。空なら管理者
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('external_user_id')->nullable()->unique()->after('id')->constrained('external_users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('external_user_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_user_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_user_id')->constrained('external_users')->cascadeOnDelete();
            // db_setlists.id は int unsigned のため、bigint を生成する foreignId ではなく型を合わせて追加する
            $table->unsignedInteger('db_setlist_id');
            $table->foreign('db_setlist_id')->references('id')->on('db_setlists')->cascadeOnDelete();
            $table->date('attended_date')->nullable();
            $table->string('venue')->nullable();
            $table->timestamps();

            // 同じセットリストパターンへの複数回参加を許可するため、ユニーク制約は付けない
            $table->index(['external_user_id', 'db_setlist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_user_attendances');
    }
};

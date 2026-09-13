<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeline_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_user_attendance_id')->constrained('external_user_attendances')->cascadeOnDelete();
            $table->foreignId('external_user_id')->constrained('external_users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeline_comments');
    }
};

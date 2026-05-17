<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_favorite_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('track_id')->constrained('music_tracks')->onDelete('cascade');
            $table->integer('order')->default(0); // Thứ tự trong playlist yêu thích
            $table->timestamps();
            
            // Mỗi user chỉ có thể yêu thích 1 bài 1 lần
            $table->unique(['user_id', 'track_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_favorite_tracks');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('music_albums', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên album (nhạc không lời, nhạc vàng...)
            $table->string('description')->nullable();
            $table->string('cover_image')->nullable(); // Ảnh bìa album
            $table->integer('order')->default(0); // Thứ tự hiển thị
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('music_albums');
    }
};
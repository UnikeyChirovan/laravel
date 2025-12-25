<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('music_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained('music_albums')->onDelete('cascade');
            $table->string('title'); // Tên bài hát
            $table->string('artist')->nullable(); // Ca sĩ
            $table->string('file_path'); // Đường dẫn file mp3 trong storage
            $table->integer('duration')->nullable(); // Thời lượng (giây)
            $table->integer('order')->default(0); // Thứ tự trong album
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('music_tracks');
    }
};
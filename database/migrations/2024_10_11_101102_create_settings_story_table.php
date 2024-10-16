<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('settings_story', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('background_story_id')->nullable(); // Liên kết với bảng background_story
            $table->string('font_family')->default('Arial');
            $table->integer('font_size')->default(16);
            $table->float('line_height')->default(1.5);
            $table->boolean('hasSettings')->default(false); // Thêm cột hasSettings
            $table->timestamps();

            // Liên kết với bảng users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // Liên kết với bảng background_story
            $table->foreign('background_story_id')->references('id')->on('background_story')->onDelete('set null');
        });
    }


    public function down(): void {
        Schema::dropIfExists('settings_story');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideoManagersTable extends Migration
{
    public function up()
    {
        Schema::create('video_managers', function (Blueprint $table) {
            $table->id();
            $table->string('video_name');
            $table->string('video_path');
            $table->text('description')->nullable(); // Thêm mô tả
            $table->string('thumbnail')->nullable(); // Thêm ảnh thumbnail
            $table->integer('episode_number')->nullable(); // Thêm số tập
            $table->boolean('is_featured')->default(false); // Thêm cột đánh dấu nổi bật
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('video_managers');
    }
}

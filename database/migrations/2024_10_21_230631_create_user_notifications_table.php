<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Tiêu đề của thông báo
            $table->string('content_path'); // Đường dẫn tới file txt chứa nội dung
            $table->json('image_paths')->nullable(); // Đường dẫn tới các file hình ảnh
            $table->timestamps(); // Thời gian tạo và cập nhật thông báo
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_notifications');
    }
}

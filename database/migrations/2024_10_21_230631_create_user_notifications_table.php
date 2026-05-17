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
            $table->string('title'); 
            $table->string('content_path'); 
            $table->enum('page', ['home', 'maps']);
            $table->json('image_paths')->nullable(); 
            $table->timestamps(); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_notifications');
    }
}

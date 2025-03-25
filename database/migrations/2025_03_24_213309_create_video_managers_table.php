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
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('video_managers');
    }
}

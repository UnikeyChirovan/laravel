<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChaptersTable extends Migration
{
    public function up()
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id(); 
            $table->string('title');
            $table->string('author'); 
            $table->integer('chapter_number'); 
            $table->string('file_path')->nullable(); 
            $table->timestamps(); 
        });
    }
    public function down()
    {
        Schema::dropIfExists('chapters');
    }
}

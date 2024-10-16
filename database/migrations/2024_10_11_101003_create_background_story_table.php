<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('background_story', function (Blueprint $table) {
            $table->id();
            $table->string('background_image_name');
            $table->string('background_image_path');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('background_story');
    }
};

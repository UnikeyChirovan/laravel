<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('settings_story', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('background_story_id')->nullable();
            
            // Background settings
            $table->enum('background_mode', ['none', 'no-image', 'with-image'])->default('none');
            $table->enum('screen_mode', ['full', 'partial', 'custom'])->default('full');
            
            // Font settings
            $table->string('font_family')->default('Arial');
            $table->integer('font_size')->default(16);
            $table->float('line_height')->default(1.5);
            
            // Display settings
            $table->integer('brightness')->default(100);
            $table->enum('mode', ['day', 'night'])->default('day');
            $table->boolean('yellow_light_mode')->default(false);
            
            // Background style settings (cho container chính)
            $table->enum('background_style', ['solid', 'gradient'])->default('solid');
            $table->string('background_color')->default('#ffffff');
            $table->string('selected_gradient')->nullable();
            $table->float('background_opacity', 3, 2)->default(1.00);
            
            // Custom background styles (cho reading area khi screenMode = 'custom')
            $table->enum('custom_background_style', ['solid', 'gradient'])->default('solid');
            $table->string('custom_background_color')->default('#ffffff');
            $table->string('custom_selected_gradient')->nullable();
            $table->float('custom_background_opacity', 3, 2)->default(1.00);
            
            // Meta
            $table->boolean('hasSettings')->default(false);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('background_story_id')->references('id')->on('background_story')->onDelete('set null');
        });
    }

    public function down(): void {
        Schema::dropIfExists('settings_story');
    }
};
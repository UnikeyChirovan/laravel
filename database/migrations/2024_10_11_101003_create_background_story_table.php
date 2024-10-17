<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void {
        Schema::create('background_story', function (Blueprint $table) {
            $table->id();
            $table->string('background_image_name');
            $table->string('background_image_path')->nullable(); 
            $table->timestamps();
        });

        DB::table('background_story')->insert([
            'background_image_name' => 'Không Hình Nền',
            'background_image_path' => null, 
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down(): void {
        Schema::dropIfExists('background_story');
    }
};

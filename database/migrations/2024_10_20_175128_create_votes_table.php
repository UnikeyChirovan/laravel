<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
            $table->enum('choice', ['1', '2', '3']); 
            $table->timestamp('voted_at');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('votes');
    }
};

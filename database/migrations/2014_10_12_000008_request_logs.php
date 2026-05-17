<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->integer('request_count')->default(0);
            $table->timestamp('last_request_at')->nullable();
            $table->timestamps();

            $table->unique(['ip_address', 'user_agent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};

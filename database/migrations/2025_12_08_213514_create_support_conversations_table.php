<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['pending', 'active', 'resolved', 'closed'])->default('pending');
            $table->unsignedBigInteger('assigned_to')->nullable(); // Manager/Admin đang xử lý
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->integer('rating')->nullable(); // 1-5 stars
            $table->text('rating_comment')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('assigned_to');
            $table->index('last_message_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_conversations');
    }
};
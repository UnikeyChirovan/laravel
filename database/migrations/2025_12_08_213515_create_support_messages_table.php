<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('support_conversation_id');
            $table->unsignedBigInteger('sender_id');
            $table->enum('sender_type', ['user', 'support']); // user hoặc support team
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('support_conversation_id')->references('id')->on('support_conversations')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('support_conversation_id');
            $table->index('sender_id');
            $table->index('is_read');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
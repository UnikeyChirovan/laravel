<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('commentable_type'); // 'chapter' hoặc 'episode'
            $table->unsignedBigInteger('commentable_id'); // chapter_id hoặc episode_id
            $table->unsignedBigInteger('parent_id')->nullable(); // Cho reply
            $table->text('content');
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('comments')->onDelete('cascade');

            // Indexes
            $table->index(['commentable_type', 'commentable_id']);
            $table->index('parent_id');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
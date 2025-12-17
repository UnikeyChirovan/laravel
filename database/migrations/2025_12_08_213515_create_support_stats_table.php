<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('support_conversation_id');
            $table->unsignedBigInteger('manager_id'); // Manager/Admin xử lý
            $table->integer('response_time')->nullable(); // Thời gian phản hồi đầu tiên (giây)
            $table->integer('resolution_time')->nullable(); // Thời gian giải quyết (giây)
            $table->integer('message_count')->default(0); // Số tin nhắn đã gởi
            $table->boolean('was_transferred')->default(false); // Có được chuyển tiếp không
            $table->unsignedBigInteger('transferred_from')->nullable(); // Từ manager nào
            $table->timestamp('claimed_at')->nullable(); // Thời điểm claim
            $table->timestamp('resolved_at')->nullable(); // Thời điểm resolve
            $table->timestamps();

            // Foreign keys
            $table->foreign('support_conversation_id')->references('id')->on('support_conversations')->onDelete('cascade');
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('transferred_from')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('support_conversation_id');
            $table->index('manager_id');
            $table->index('claimed_at');
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_stats');
    }
};
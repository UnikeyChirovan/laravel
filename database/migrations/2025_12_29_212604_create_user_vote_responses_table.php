<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserVoteResponsesTable extends Migration
{
    public function up()
    {
        Schema::create('user_vote_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_vote_id')->constrained('notification_votes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('option_id');
            $table->timestamps();
            
            // Mỗi user chỉ vote 1 lần cho mỗi notification
            $table->unique(['notification_vote_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_vote_responses');
    }
}
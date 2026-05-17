<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationVotesTable extends Migration
{
    public function up()
    {
        Schema::create('notification_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('user_notifications')->onDelete('cascade');
            $table->string('question');
            $table->json('options'); // [{id: 1, text: 'Option 1'}, {id: 2, text: 'Option 2'}]
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_votes');
    }
}
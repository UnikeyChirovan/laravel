<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToUserNotificationsTable extends Migration
{
    public function up()
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->enum('type', ['normal', 'voting'])->default('normal')->after('page');
        });
    }

    public function down()
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}

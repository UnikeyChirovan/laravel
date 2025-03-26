<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEpisodeNumberToVideoManagers extends Migration
{
    public function up()
    {
        Schema::table('video_managers', function (Blueprint $table) {
            $table->integer('episode_number')->after('video_path')->nullable();
        });
    }

    public function down()
    {
        Schema::table('video_managers', function (Blueprint $table) {
            $table->dropColumn('episode_number');
        });
    }
}

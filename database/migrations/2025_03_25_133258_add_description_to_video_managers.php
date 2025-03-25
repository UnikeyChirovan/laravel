<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionToVideoManagers extends Migration
{
    public function up()
    {
        Schema::table('video_managers', function (Blueprint $table) {
            $table->text('description')->nullable()->after('video_path');
        });
    }

    public function down()
    {
        Schema::table('video_managers', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
}

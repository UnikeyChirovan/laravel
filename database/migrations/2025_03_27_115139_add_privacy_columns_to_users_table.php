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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('show_email')->default(true);
            $table->boolean('show_phone_number')->default(true);
            $table->boolean('show_biography')->default(true);
            $table->boolean('show_hobbies')->default(true);
            $table->boolean('show_occupation')->default(true);
            $table->boolean('show_birthday')->default(true);
            $table->boolean('show_gender')->default(true);
            $table->boolean('show_address')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'show_email',
                'show_phone_number',
                'show_biography',
                'show_hobbies',
                'show_occupation',
                'show_birthday',
                'show_gender',
                'show_address',
            ]);
        });
    }

};

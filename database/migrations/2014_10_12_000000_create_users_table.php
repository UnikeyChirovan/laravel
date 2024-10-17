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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('avatar')->nullable();
            $table->string('cover')->nullable();
            $table->string('username')->unique();
            $table->string('name');
            $table->string('nickname');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('occupation')->nullable(); 
            $table->date('birthday')->nullable();
            $table->enum('gender', ['Nam', 'Nữ'])->nullable();
            $table->string('address')->nullable();
            $table->text('biography')->nullable(); 
            $table->text('hobbies')->nullable(); 
            $table->string('phone_number')->nullable(); 
            $table->integer('cover_position')->default(0); 
            $table->rememberToken();
            $table->timestamp('login_at')->nullable();
            $table->timestamp('change_password_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

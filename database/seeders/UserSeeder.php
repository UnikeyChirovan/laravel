<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table("users")->insert([
            [
            "username" => "selorson",
            "name" =>"Selorson Tales",
            "nickname" => "Selorson",
            "email" => "selorson@gmail.com",
            "password" => Hash::make("123"),
            "department_id" => "1",
            "status_id" => "1"
            ],
            [
            "username" => "minato",
            "name" =>"namikaze minato",
            "nickname"=>"Minato",
            "email" => "minato@gmail.com",
            "password" => Hash::make("123"),
            "department_id" => "2",
            "status_id" => "2"
            ]
        ]);
    }
}

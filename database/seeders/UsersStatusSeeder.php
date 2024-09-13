<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table("users_status")->insert([
            ["name" => "Hoạt động"],
            ["name" => "Tạm khóa"],
            ["name" => "Cấm 3 ngày"],
            ["name" => "Cấm vĩnh viễn"],
            ["name" => "Chưa xác thực"]
        ]);
    }
}

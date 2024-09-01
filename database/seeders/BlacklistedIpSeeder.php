<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlacklistedIpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('blacklisted_ips')->insert([
            ['ip_address' => '123.456.789.000', 'user_agent' => null, 'reason' => 'Spam activities'],
            ['ip_address' => '987.654.321.000', 'user_agent' => 'Mozilla/5.0', 'reason' => 'Brute force attempt'],
            ['ip_address' => '123.456.0.1', 'user_agent' => 'Chrome/91.0', 'reason' => 'Suspicious behavior'],
            // Thêm các IP khác tại đây
        ]);
    }
}

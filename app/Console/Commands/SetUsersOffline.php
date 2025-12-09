<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use App\Events\UserOnlineStatus;
use Illuminate\Console\Command;

class SetUsersOffline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:set-offline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set users offline if they have been inactive for 5 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Lấy thời điểm 5 phút trước
        $threshold = Carbon::now()->subMinutes(5);
        
        // Tìm users đang online nhưng không hoạt động > 5 phút
        $users = User::where('is_online', true)
            ->where('last_active_at', '<', $threshold)
            ->get();

        $count = 0;
        foreach ($users as $user) {
            // Set offline
            $user->update(['is_online' => false]);
            
            // Broadcast offline status (chỉ nếu user cho phép)
            if ($user->show_online_status) {
                broadcast(new UserOnlineStatus($user->id, false))->toOthers();
            }
            
            $count++;
            $this->info("User {$user->name} (ID: {$user->id}) set to offline");
        }

        $this->info("✓ Total users set offline: {$count}");
        return 0;
    }
}
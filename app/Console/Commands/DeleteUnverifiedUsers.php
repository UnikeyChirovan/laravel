<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class DeleteUnverifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:delete-unverified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete users with status_id 5 who haven\'t verified their email.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deleted = User::where('status_id', 5)
                       ->whereNull('email_verified_at')
                       ->delete();

        $this->info("Deleted $deleted unverified users.");
    }
}

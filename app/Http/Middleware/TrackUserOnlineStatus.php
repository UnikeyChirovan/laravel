<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use App\Events\UserOnlineStatus;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserOnlineStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Chỉ track nếu user cho phép hiển thị online status
            if ($user->show_online_status) {
                $wasOffline = !$user->is_online;
                
                // Update last_active_at và is_online
                $user->update([
                    'is_online' => true,
                    'last_active_at' => Carbon::now()
                ]);

                // Chỉ broadcast khi chuyển từ offline -> online
                // Tránh spam broadcast mỗi request
                if ($wasOffline) {
                    broadcast(new UserOnlineStatus($user->id, true))->toOthers();
                }
            }
        }

        return $next($request);
    }
}
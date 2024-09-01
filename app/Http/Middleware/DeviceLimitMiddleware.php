<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\DeviceInfo;
use Illuminate\Support\Facades\Auth;

class DeviceLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Đếm số lượng thiết bị đã đăng nhập của người dùng
        $deviceCount = DeviceInfo::where('user_id', $user->id)->count();

        // Nếu đã đăng nhập trên 2 thiết bị, chặn và thông báo
        if ($deviceCount >= 2) {
            return response()->json([
                'message' => 'Vui lòng đăng xuất một thiết bị trước khi tiếp tục đăng nhập.',
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\BlacklistedIp;
use Symfony\Component\HttpFoundation\Response;

class BlacklistMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        // Kiểm tra blacklist IP hoặc user agent
        $blacklisted = BlacklistedIp::where('ip_address', $ip)
            ->orWhere('user_agent', $userAgent)
            ->first();

        if ($blacklisted) {
            return response()->json([
                'message' => $blacklisted->reason ?? 'Bạn đã bị chặn, vui lòng liên hệ quản trị viên để xử lý.'
            ], 403);
        }

        return $next($request);
    }
}

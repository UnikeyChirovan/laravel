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
        $blacklisted = BlacklistedIp::where('ip_address', $ip)
            ->where('user_agent', $userAgent)
            ->first();
        if ($blacklisted) {
            return response()->json([
                'message' => $blacklisted->reason ?? 'Bạn đã bị chặn, vui lòng liên hệ quản trị viên để xử lý.'
            ], 403);
        }
        return $next($request);
    }
}

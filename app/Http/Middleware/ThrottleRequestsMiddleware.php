<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\RequestLog;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequestsMiddleware
{
    protected $maxRequests = 10;

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $userAgent = substr($request->userAgent() ?? 'unknown', 0, 255);
        $currentTime = Carbon::now();
        $requestLog = RequestLog::where('ip_address', $ip)
                                ->where('user_agent', $userAgent)
                                ->first();
        if ($requestLog) {
            if ($requestLog->last_request_at && $currentTime->diffInMinutes($requestLog->last_request_at) < 1) {
                if ($requestLog->request_count >= $this->maxRequests) {
                    return response()->json(['message' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau 1 phút nữa.'], 429);
                }
                $requestLog->increment('request_count');
                $requestLog->last_request_at = $currentTime;
                $requestLog->save();
            } else {
                $requestLog->update([
                    'request_count' => 1,
                    'last_request_at' => $currentTime,
                ]);
            }
        } else {
            RequestLog::create([
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'request_count' => 1,
                'last_request_at' => $currentTime,
            ]);
        }

        return $next($request);
    }
}

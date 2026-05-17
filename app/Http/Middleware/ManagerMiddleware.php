<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class ManagerMiddleware
{
    /**
     * Handle an incoming request.
     * Cho phép Admin (department_id = 1) HOẶC Manager (department_id = 3)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Lấy user từ token
            $user = JWTAuth::parseToken()->authenticate();
            
            // Kiểm tra department_id
            if (in_array($user->department_id, [1, 3])) {
                return $next($request);
            }
            
            return response()->json([
                'message' => 'Bạn không có quyền truy cập. Chỉ Admin hoặc Quản lý mới được phép.'
            ], 403);
            
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Token không hợp lệ hoặc đã hết hạn.'
            ], 401);
        }
    }
}
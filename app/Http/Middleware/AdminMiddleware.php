<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $payload = JWTAuth::getPayload();
            $isAdmin = $payload->get('isAdmin');
            if ($isAdmin) {
                return $next($request);
            } else {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } catch (Exception $e) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

        // public function handle(Request $request, Closure $next)
        //     {
        //         if (Auth::check() && Auth::user()->department_id == 1) {
        //             return $next($request);
        //         }
                
        //         return response()->json(['message' => 'Unauthorized'], 403);
        //     }
}

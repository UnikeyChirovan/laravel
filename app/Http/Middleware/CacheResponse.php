<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


// class CacheResponse
// {
//     public function handle($request, Closure $next)
//         {
//             if ($request->isMethod('get')) {
//                 $key = md5($request->fullUrl());
//                 Log::info('Checking cache for key: ' . $key);
//                 $cachedResponse = Cache::get($key);
//                 if ($cachedResponse) {
//                     Log::info('Cache hit for key: ' . $key);
//                     return response($cachedResponse);
//                 }
//                 Log::info('Cache miss for key: ' . $key);
//                 $startTime = microtime(true);
//                 $response = $next($request);
//                 $endTime = microtime(true);
//                 $executionTime = $endTime - $startTime;
//                 Cache::put($key, $response->getContent(), 7200);
//                 Log::info('Response cached for key: ' . $key . '. Execution time: ' . $executionTime . ' seconds.');
//                 return $response;
//             }
//             return $next($request);
//         }

// }

class CacheResponse
{
    public function handle($request, Closure $next)
    {
        if ($request->isMethod('get')) {
            $key = md5($request->fullUrl());

            if (app()->environment('local')) {
                Log::info('Checking cache for key: ' . $key);
            }

            $cachedResponse = Cache::remember($key, 7200, function () use ($next, $request, $key) {
                $startTime = microtime(true);
                $response = $next($request);
                $endTime = microtime(true);
                $executionTime = $endTime - $startTime;

                if ($response->status() === 200) {
                    Log::info('Response cached for key: ' . $key . '. Execution time: ' . $executionTime . ' seconds.');
                    return $response->getContent();
                }

                return null; // Không lưu cache nếu status không phải là 200
            });

            if ($cachedResponse) {
                return response($cachedResponse);
            }
        }

        return $next($request);
    }
}
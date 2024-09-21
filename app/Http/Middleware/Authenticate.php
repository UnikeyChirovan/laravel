<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            // Trả về phản hồi JSON thay vì chuyển hướng
            abort(response()->json(['error' => 'Unauthenticated.'], 401));
        }
    }
}

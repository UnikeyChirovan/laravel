<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{




    public function me()
    {   
        try{
            return response()->json(auth()->user());
        }catch (JWTException $exception ){
           return response()->json(['error' => 'Unauthorized'], 401);
        }
        
    }

 
}


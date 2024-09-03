<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('/register', [UserController::class, 'register']);

Route::group([
    'middleware' => ['api', 'blacklist', 'throttle.requests'],
    'prefix' => 'auth'
], 
function () {
    Route::get('/user/{id}', [UserController::class, 'show']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/create', [UserController::class, 'create']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}/edit', [UserController::class, 'edit']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/refresh', [UserController::class, 'refreshToken']);
    Route::get('/device-info', [UserController::class, 'getAllDeviceInfo']);
    Route::get('/blacklist', [UserController::class, 'getAllBlacklist']);
    Route::post('/transfer-to-blacklist/{userId}', [UserController::class, 'transferToBlacklist']);
});


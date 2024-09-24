<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\ProfileController;


Route::options('/{any}', function (Request $request) {
    return response()->json([], 204);
})->where('any', '.*');

// Authentication
Route::group([
    'prefix' => 'auth',
    'middleware' => ['api', 'throttle.requests'],
], function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('blacklist')->name('auth.login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/force-logout', [AuthController::class, 'forceLogout'])->name('auth.forceLogout');
    Route::get('/verify-email', [AuthController::class, 'verifyEmail'])->name('auth.verifyEmail');
    Route::post('/password-reset-request', [AuthController::class, 'sendResetLinkEmail'])->name('auth.passwordResetRequest');
    Route::post('/password-reset', [AuthController::class, 'resetPassword'])->name('auth.passwordReset');
    Route::post('/refresh', [AuthController::class, 'refreshToken'])->name('auth.refreshToken');
});

// User
Route::group([
    'prefix' => 'users',
    'middleware' => ['api', 'auth:api', 'blacklist', 'throttle.requests'],
], function () {
    Route::get('/create', [UserController::class, 'create'])->name('users.create');
    Route::get('/', [UserController::class, 'index'])->name('users.index');
    Route::post('/', [UserController::class, 'store'])->name('users.store');
    Route::get('/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/device-info', [UserController::class, 'getAllDeviceInfo'])->name('users.deviceInfo');
    Route::get('/blacklist', [UserController::class, 'getAllBlacklist'])->name('users.blacklist');
    Route::post('/transfer-to-blacklist/{userId}', [UserController::class, 'transferToBlacklist'])->name('users.transferToBlacklist');
});


Route::group([
    'prefix' => 'link',
    'middleware' => ['api', 'auth:api', 'blacklist', 'throttle.requests'],
], function () {
    Route::post('/upload/avatar', [UploadController::class, 'uploadAvatar']);
    Route::post('/upload/cover', [UploadController::class, 'uploadCover']);
    Route::delete('/{id}/avatar', [UploadController::class, 'deleteAvatar']);
    Route::delete('/{id}/cover', [UploadController::class, 'deleteCover']);

});
Route::group([
    'prefix' => 'profile',
    'middleware' => ['api', 'auth:api', 'blacklist', 'throttle.requests'],
], function () {
    Route::get('/{id}', [ProfileController::class, 'show'])->name('users.showprofile');
    Route::get('/{id}/edit', [ProfileController::class, 'edit'])->name('users.editprofile');
    Route::put('/{id}', [ProfileController::class, 'update'])->name('users.updateprofile');
    Route::put('/{id}/position', [ProfileController::class, 'updatePosition']);
});



//    Route::post('link/upload/avatar', [UploadController::class, 'uploadAvatar']);
//    Route::post('link/upload/cover', [UploadController::class, 'uploadCover']);
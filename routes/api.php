<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CompanyInfoController;
use App\Http\Controllers\UserChapterController;


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
Route::group([
    'prefix' => 'noauth',
    'middleware' => ['api', 'throttle.requests'],
], function () {
    Route::get('/contact', [CompanyInfoController::class, 'getInfo']);
    Route::post('/contact', [ContactController::class, 'store']);
    Route::get('/contacts', [ContactController::class, 'index']);
    Route::delete('/contacts/{id}', [ContactController::class, 'destroy']);
    Route::post('/reply-email', [ContactController::class, 'reply']);


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
    Route::delete('/blacklist/{id}', [UserController::class, 'deleteFromBlacklist'])->name('users.deleteFromBlacklist');
    Route::get('/request-logs', [UserController::class, 'getAllRequestLogs'])->name('users.requestLogs');
    Route::delete('/request-log/{id}', [UserController::class, 'deleteRequestLog'])->name('users.deleteRequestLog');
    Route::delete('/request-logs', [UserController::class, 'deleteAllRequestLogs'])->name('users.deleteAllRequestLogs');
    Route::post('/transfer-from-request-log/{id}', [UserController::class, 'transferToBlacklistFromRequestLog'])->name('users.transferFromRequestLog');
});


Route::group([
    'prefix' => 'link',
    'middleware' => ['api', 'auth:api', 'blacklist', 'throttle.requests'],
], function () {
    Route::post('/upload/avatar', [UploadController::class, 'uploadAvatar']);
    Route::post('/upload/cover', [UploadController::class, 'uploadCover']);
    Route::patch('/update/cover-position', [UploadController::class, 'updateCoverPosition']);
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

Route::prefix('story')->middleware(['api', 'auth:api'])->group(function () {
    Route::get('/chapters', [UploadController::class, 'index']);
    Route::get('/chapters/{id}', [UploadController::class, 'getChapter']);
    Route::get('/backgrounds', [StoryController::class, 'getBackgrounds'])->name('backgrounds.get');
    Route::get('/backgrounds/{id}', [StoryController::class, 'getImage']);
    Route::post('/save-settings', [StoryController::class, 'saveSettings'])->name('settings.save');
    Route::get('/{user_id}/settings', [StoryController::class, 'getSettings'])->name('settings.get');
    Route::put('/settings', [StoryController::class, 'updateSettings'])->name('settings.update');

    Route::post('/user-chapter', [UserChapterController::class, 'storeCurrentChapter']); 
    Route::put('/user-chapter', [UserChapterController::class, 'updateCurrentChapter']);
    Route::get('/user-chapter', [UserChapterController::class, 'getLastReadChapter']); 
    Route::middleware('admin')->group(function () {
        Route::post('/upload-background', [StoryController::class, 'uploadBackground'])->name('admin.upload-background');
        Route::put('/chapters/{id}', [UploadController::class, 'updateChapter']);
        Route::post('/chapters', [UploadController::class, 'createChapter']);
        Route::delete('/chapters/{id}', [UploadController::class, 'destroy']);
        Route::put('/backgrounds/{id}', [StoryController::class, 'updateBackground'])->name('backgrounds.update');
        Route::delete('/backgrounds/{id}', [StoryController::class, 'deleteBackground'])->name('backgrounds.delete');
    });
});

Route::group([
    'prefix' => 'vote',
    'middleware' => ['api', 'blacklist', 'throttle.requests'],
], function () {
    Route::post('/createOrUpdate', [VoteController::class, 'createOrUpdateVote'])->middleware('auth:api'); // Tạo hoặc cập nhật vote
    Route::get('/getUserVote', [VoteController::class, 'getUserVote'])->middleware('auth:api'); // Lấy kết quả vote của người dùng
    Route::get('/results', [VoteController::class, 'getVoteResults']);
});



<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HeroSlideController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\CompanyInfoController;
use App\Http\Controllers\UserChapterController;
use App\Http\Controllers\ImageManagerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\VideoManagerController;
use App\Http\Controllers\FutureProjectController;
use App\Http\Controllers\UserNotificationController;



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
    Route::post('/super-force-logout', [AuthController::class, 'superForceLogout']);
    Route::middleware('auth:api')->delete('/delete-account', [AuthController::class, 'selfDeleteAccount']);
    Route::get('/verify-email', [AuthController::class, 'verifyEmail'])->name('auth.verifyEmail');
    Route::post('/password-reset-request', [AuthController::class, 'sendResetLinkEmail'])->name('auth.passwordResetRequest');
    Route::post('/password-reset', [AuthController::class, 'resetPassword'])->name('auth.passwordReset');
    Route::post('/refresh', [AuthController::class, 'refreshToken'])->name('auth.refreshToken');
});
Route::group([
    'prefix' => 'noauth',
    'middleware' => ['api', 'throttle.requests'],
], function () {
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
    Route::get('/explore', [UserController::class, 'explore'])->name('users.explore');
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
    Route::post('/user-chapter', [UserChapterController::class, 'saveOrUpdateCurrentChapter']); 
    Route::get('/user-chapter', [UserChapterController::class, 'getLastReadChapter']); 
    Route::get('/guest-chapter/{id}', [UserChapterController::class, 'getGuestLastReadChapter']); 
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
    Route::post('/createOrUpdate', [VoteController::class, 'createOrUpdateVote'])->middleware('auth:api');
    Route::get('/getUserVote', [VoteController::class, 'getUserVote'])->middleware('auth:api'); 
    Route::get('/results', [VoteController::class, 'getVoteResults']);
});

Route::prefix('newsletter')->middleware(['api','blacklist', 'throttle.requests'])->group(function () {
    Route::post('/subscribe', [NewsletterController::class, 'subscribe']);
    Route::get('/unsubscribe', [NewsletterController::class, 'unsubscribe']);
    Route::middleware('admin')->group(function () {
    Route::post('/notifications/create', [NotificationController::class, 'create']);
    Route::get('/notifications', [NotificationController::class, 'getAll']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'delete']);
    Route::get('/emails', [NewsletterController::class, 'getEmails']);
    });
});
Route::group([
    'prefix' => 'user-notifications',
    'middleware' => ['throttle.requests'],
], function () {
    Route::get('/page-options', [UserNotificationController::class, 'getPageOptions']);
    Route::get('/', [UserNotificationController::class, 'index']);
    Route::post('/', [UserNotificationController::class, 'store'])->middleware('admin');
    Route::get('/{id}', [UserNotificationController::class, 'show']);
    Route::put('/{id}/text', [UserNotificationController::class, 'updateText'])->middleware('admin');
    Route::delete('/{id}', [UserNotificationController::class, 'destroy'])->middleware('admin');
});
Route::group([
    'prefix' => 'image-manager',
    'middleware' => [ 'throttle.requests'],
], function () {
    Route::post('/upload', [ImageManagerController::class, 'uploadImage'])->middleware('admin');
    Route::get('/', [ImageManagerController::class, 'getImages']);
    Route::get('/{id}', [ImageManagerController::class, 'getImage']);
    Route::put('/{id}', [ImageManagerController::class, 'updateImage'])->middleware('admin');
    Route::delete('/{id}', [ImageManagerController::class, 'deleteImage'])->middleware('admin');
});
Route::group([
    'prefix' => 'categories',
    'middleware' => [ 'throttle.requests'],
], function () {
    Route::get('/page-options', [CategoryController::class, 'getPageOptions']);
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{id}', [CategoryController::class, 'show']);
    Route::post('/', [CategoryController::class, 'store'])->middleware('admin');
    Route::put('/{id}', [CategoryController::class, 'update'])->middleware('admin');
    Route::delete('/{id}', [CategoryController::class, 'destroy'])->middleware('admin');
});
Route::group([
    'middleware' => [ 'throttle.requests'],
], function () {
    Route::get('/company-info', [CompanyInfoController::class, 'index']);
    Route::get('/company-info/{id}', [CompanyInfoController::class, 'show'])->middleware('admin');
    Route::post('/company-info', [CompanyInfoController::class, 'store'])->middleware('admin');
    Route::put('/company-info/{id}', [CompanyInfoController::class, 'update'])->middleware('admin');
    Route::delete('/company-info/{id}', [CompanyInfoController::class, 'destroy'])->middleware('admin');
});

Route::group([
    'prefix' => 'sections',
    'middleware' => [ 'throttle.requests'],
], function () {
    Route::get('/', [SectionController::class, 'index']); 
    Route::post('/', [SectionController::class, 'createSection'])->middleware('admin');
    Route::get('/{id}', [SectionController::class, 'getSection']); 
    Route::put('/{id}', [SectionController::class, 'updateSection'])->middleware('admin');
    Route::delete('/{id}', [SectionController::class, 'destroy'])->middleware('admin');
});

Route::group([
    'prefix' => 'hero-slides',
    'middleware' => ['throttle.requests'],
], function () {
    Route::get('/', [HeroSlideController::class, 'index']);
    Route::get('/{id}', [HeroSlideController::class, 'show']);
    Route::post('/', [HeroSlideController::class, 'store'])->middleware('admin');
    Route::put('/{id}', [HeroSlideController::class, 'update'])->middleware('admin');
    Route::delete('/{id}', [HeroSlideController::class, 'destroy'])->middleware('admin');
});


Route::group([
    'prefix' => 'future-projects',
    'middleware' => ['throttle.requests'],
], function () {
    Route::get('/', [FutureProjectController::class, 'index']);
    Route::get('/{id}', [FutureProjectController::class, 'show']);
    Route::post('/', [FutureProjectController::class, 'store'])->middleware('admin');
    Route::put('/{id}', [FutureProjectController::class, 'update'])->middleware('admin');
    Route::delete('/{id}', [FutureProjectController::class, 'destroy'])->middleware('admin');
});


Route::group([
    'prefix' => 'features',
    'middleware' => ['throttle.requests'],
], function () {
    Route::get('/', [FeatureController::class, 'index']);
    Route::get('/{id}', [FeatureController::class, 'show']);
    Route::post('/', [FeatureController::class, 'store'])->middleware('admin');
    Route::put('/{id}', [FeatureController::class, 'update'])->middleware('admin');
    Route::delete('/{id}', [FeatureController::class, 'destroy'])->middleware('admin');
});


Route::group([
    'prefix' => 'videos',
    'middleware' => ['throttle.requests'],
], function () {
    Route::post('/upload', [VideoManagerController::class, 'uploadVideo'])->middleware('admin');
    Route::get('/featured', [VideoManagerController::class, 'getFeaturedVideo']);
    Route::get('/', [VideoManagerController::class, 'getVideos']);
    Route::post('/user-episode', [UserChapterController::class, 'saveOrUpdateCurrentEpisode']); 
    Route::get('/user-episode', [UserChapterController::class, 'getLastWatchEpisode']); 
    Route::get('/guest-episode/{id}', [UserChapterController::class, 'getGuestLastWatchEpisode']); 
    Route::get('/{id}', [VideoManagerController::class, 'getVideo']);
    Route::put('/{id}', [VideoManagerController::class, 'updateVideo'])->middleware('admin');
    Route::delete('/{id}', [VideoManagerController::class, 'deleteVideo'])->middleware('admin');
    Route::put('/{id}/set-featured', [VideoManagerController::class, 'setFeaturedVideo'])->middleware('admin');
});

Route::group([
    'prefix' => 'guest',
    'middleware' => ['api', 'auth:api', 'blacklist', 'throttle.requests'],
], function () {
    Route::get('/{id}', [ProfileController::class, 'showGuest'])->name('users.showguest');
});
Route::group([
    'prefix' => 'private',
    'middleware' => ['api', 'auth:api', 'blacklist', 'throttle.requests'],
], function () {
    Route::post('/settings/{id}', [ProfileController::class, 'updateVisibilitySettings']);
    Route::get('/settings/{id}', [ProfileController::class, 'getVisibilitySettings']);
    Route::get('/privacy-settings', [ProfileController::class, 'getPrivacySettings']);
    Route::put('/privacy-settings', [ProfileController::class, 'updatePrivacySettings']);
});
Route::group([
    'prefix' => 'social',
    'middleware' => ['api', 'auth:api', 'blacklist', 'throttle.requests'],
], function () {
    Route::post('/follow/{id}', [FollowController::class, 'follow']);
    Route::delete('/unfollow/{id}', [FollowController::class, 'unfollow']); 
    Route::post('/block/{id}', [FollowController::class, 'block']); 
    Route::delete('/unblock/{id}', [FollowController::class, 'unblock']); 
    Route::get('/is-following/{id}', [FollowController::class, 'isFollowing']); 
    Route::get('/is-blocked/{id}', [FollowController::class, 'isBlocked']); 
    Route::get('/followed-users', [FollowController::class, 'followedUsers']);
    Route::get('/blocked-users', [FollowController::class, 'blockedUsers']); 
    Route::get('/follow-stats/{userId}', [FollowController::class, 'getFollowStats']);
    Route::get('/is-mutual-follow/{id}', [FollowController::class, 'isMutualFollow']);
});
Route::group([
    'prefix' => 'chat',
    'middleware' => ['api', 'auth:api', 'blacklist', 'throttle.requests'],
], function () {
    // Conversations
    Route::get('/conversations', [App\Http\Controllers\ConversationController::class, 'index']);
    Route::post('/conversations/get-or-create', [App\Http\Controllers\ConversationController::class, 'getOrCreate']);
    Route::delete('/conversations/{id}', [App\Http\Controllers\ConversationController::class, 'destroy']);

    // Messages
    Route::post('/messages/send', [App\Http\Controllers\MessageController::class, 'sendMessage']);
    Route::get('/messages/{conversationId}', [App\Http\Controllers\MessageController::class, 'getMessages']);
    Route::post('/messages/{conversationId}/mark-read', [App\Http\Controllers\MessageController::class, 'markAsRead']);
    Route::get('/messages/unread/count', [App\Http\Controllers\MessageController::class, 'getUnreadCount']);
    
    // Delete messages
    Route::delete('/messages/{messageId}', [App\Http\Controllers\MessageController::class, 'deleteMessage']);
    Route::delete('/conversations/{conversationId}/messages', [App\Http\Controllers\MessageController::class, 'deleteAllMessages']);

    // Messaging Settings
    Route::get('/settings', [App\Http\Controllers\MessagingSettingsController::class, 'getSettings']);
    Route::put('/settings', [App\Http\Controllers\MessagingSettingsController::class, 'updateSettings']);
    Route::get('/settings/user/{userId}', [App\Http\Controllers\MessagingSettingsController::class, 'getUserSettings']);
});


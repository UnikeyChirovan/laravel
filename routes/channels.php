<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Default Laravel user channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// ============================================
// PERSONAL CHAT CHANNELS (Existing System)
// ============================================

// Private chat channel for receiving messages
Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Public channel for user online status
Broadcast::channel('user.status', function ($user) {
    return true; // Anyone can listen to user status updates
});

// ============================================
// SUPPORT CHAT CHANNELS (New System)
// ============================================

// Support chat channel for users
Broadcast::channel('support.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Support chat channel for managers
Broadcast::channel('support.manager.{managerId}', function ($user, $managerId) {
    // Chỉ manager/admin với ID đúng mới subscribe được
    return (int) $user->id === (int) $managerId && in_array($user->department_id, [1, 3]);
});

// Support chat channel for admin
Broadcast::channel('support.admin', function ($user) {
    // Chỉ admin (department_id = 1)
    return $user->department_id === 1;
});
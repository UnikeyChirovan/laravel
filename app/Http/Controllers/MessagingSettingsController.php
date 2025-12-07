<?php

namespace App\Http\Controllers;

use App\Models\UserMessagingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessagingSettingsController extends Controller
{
    // Get messaging settings
    public function getSettings()
    {
        $userId = Auth::id();
        $settings = UserMessagingSetting::getOrCreateForUser($userId);

        return response()->json($settings);
    }

    // Update messaging settings
    public function updateSettings(Request $request)
    {
        $userId = Auth::id();

        $request->validate([
            'allow_messages' => 'sometimes|boolean',
            'notifications' => 'sometimes|boolean',
            'sound' => 'sometimes|boolean',
            'show_online_status' => 'sometimes|boolean',
        ]);

        $settings = UserMessagingSetting::getOrCreateForUser($userId);
        $settings->update($request->only([
            'allow_messages',
            'notifications',
            'sound',
            'show_online_status',
        ]));

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    // Get another user's public settings (to check if can message them)
    public function getUserSettings($userId)
    {
        $settings = UserMessagingSetting::getOrCreateForUser($userId);

        // Only return public settings
        return response()->json([
            'allow_messages' => $settings->allow_messages,
            'show_online_status' => $settings->show_online_status,
        ]);
    }
}
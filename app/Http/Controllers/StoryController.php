<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BackgroundStory;
use App\Models\SettingsStory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class StoryController extends Controller
{
    public function uploadBackground(Request $request)
    {
        $request->validate([
            'background_images' => 'required|array|max:10', 
            'background_images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', 
            'background_names' => 'required|array', 
            'background_names.*' => 'required|string|max:255',
        ]);

        $backgrounds = [];
        foreach ($request->file('background_images') as $index => $file) {
            $path = $file->store('background', 'public');
            $backgroundName = $request->background_names[$index];
            $background = BackgroundStory::create([
                'background_image_name' => $backgroundName,
                'background_image_path' => $path,
            ]);
            $backgrounds[] = $background;
        }

        return response()->json([
            'message' => 'Hình ảnh đã được tải lên thành công!',
            'backgrounds' => $backgrounds
        ], 201);
    }
    
    public function getBackgrounds()
    {
        $backgrounds = BackgroundStory::all();
        return response()->json($backgrounds, 200);
    }
    
    public function getImage($id)
    {
        try {
            $background = BackgroundStory::findOrFail($id);
            return response()->json($background, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Hình nền không tìm thấy'], 404);
        }
    }
    
    public function updateBackground(Request $request, $id)
    {
        $request->validate([
            'background_image_name' => 'required|string|max:255',
        ]);

        $background = BackgroundStory::findOrFail($id);
        $background->update([
            'background_image_name' => $request->background_image_name,
        ]);

        return response()->json([
            'message' => 'Cập nhật thành công!',
            'background' => $background
        ], 200);
    }

    public function deleteBackground($id)
    {
        $background = BackgroundStory::findOrFail($id);
        if ($background->background_image_path) {
            Storage::delete('public/' . $background->background_image_path);
        }
        $background->delete();

        return response()->json([
            'message' => 'Xóa hình nền thành công!'
        ], 204);
    }
    
    public function saveSettings(Request $request)
    {
        $request->validate([
            'background_story_id' => 'nullable|exists:background_story,id',
            'background_mode' => 'nullable|in:none,no-image,with-image',
            'screen_mode' => 'nullable|in:full,partial,custom', // ✅ Thêm validation
            'font_family' => 'required|string|max:255',
            'font_size' => 'required|integer|min:8|max:100',
            'line_height' => 'required|numeric|min:0.5|max:3',
            'brightness' => 'nullable|integer|min:0|max:100',
            'mode' => 'nullable|in:day,night',
            'yellow_light_mode' => 'nullable|boolean',
            'background_style' => 'nullable|in:solid,gradient',
            'background_color' => 'nullable|string|max:50',
            'selected_gradient' => 'nullable|string|max:50',
            'background_opacity' => 'nullable|numeric|min:0|max:1',
            'custom_background_style' => 'nullable|in:solid,gradient',
            'custom_background_color' => 'nullable|string|max:50',
            'custom_selected_gradient' => 'nullable|string|max:50',
            'custom_background_opacity' => 'nullable|numeric|min:0|max:1',
        ]);
        
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Người dùng chưa đăng nhập.'], 401);
        }
        
        $settings = SettingsStory::updateOrCreate(
            ['user_id' => $user->id],
            [
                'background_story_id' => $request->background_story_id,
                'background_mode' => $request->background_mode ?? 'none',
                'screen_mode' => $request->screen_mode ?? 'full', // ✅ Lưu screen_mode
                'font_family' => $request->font_family,
                'font_size' => $request->font_size,
                'line_height' => $request->line_height,
                'hasSettings' => true,
                'brightness' => $request->brightness ?? 100,
                'mode' => $request->mode ?? 'day',
                'yellow_light_mode' => $request->yellow_light_mode ?? false,
                'background_style' => $request->background_style ?? 'solid',
                'background_color' => $request->background_color ?? '#ffffff',
                'selected_gradient' => $request->selected_gradient,
                'background_opacity' => $request->background_opacity ?? 1.0,
                'custom_background_style' => $request->custom_background_style ?? 'solid',
                'custom_background_color' => $request->custom_background_color ?? '#ffffff',
                'custom_selected_gradient' => $request->custom_selected_gradient,
                'custom_background_opacity' => $request->custom_background_opacity ?? 1.0,
            ]
        );
        
        $settings->load('backgroundStory');
        
        return response()->json([
            'message' => 'Cài đặt đã được lưu thành công!',
            'settings' => $settings
        ], 200);
    }
    
    public function getSettings()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Người dùng chưa đăng nhập.'], 401);
        }

        $settings = SettingsStory::where('user_id', $user->id)
            ->with('backgroundStory')
            ->first();

        if ($settings) {
            return response()->json([
                'settings' => $settings 
            ], 200);
        }
        
        return response()->json(['error' => 'Không tìm thấy cài đặt cho người dùng này.'], 404);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'background_story_id' => 'nullable|exists:background_story,id',
            'background_mode' => 'nullable|in:none,no-image,with-image',
            'screen_mode' => 'nullable|in:full,partial,custom', // ✅ Thêm validation
            'font_family' => 'nullable|string|max:255',
            'font_size' => 'nullable|integer|min:8|max:100',
            'line_height' => 'nullable|numeric|min:0.5|max:3',
            'brightness' => 'nullable|integer|min:0|max:100',
            'mode' => 'nullable|in:day,night',
            'yellow_light_mode' => 'nullable|boolean',
            'background_style' => 'nullable|in:solid,gradient',
            'background_color' => 'nullable|string|max:50',
            'selected_gradient' => 'nullable|string|max:50',
            'background_opacity' => 'nullable|numeric|min:0|max:1',
            'custom_background_style' => 'nullable|in:solid,gradient',
            'custom_background_color' => 'nullable|string|max:50',
            'custom_selected_gradient' => 'nullable|string|max:50',
            'custom_background_opacity' => 'nullable|numeric|min:0|max:1',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Người dùng chưa đăng nhập.'], 401);
        }

        $settings = SettingsStory::where('user_id', $user->id)->first();

        if (!$settings) {
            return response()->json(['error' => 'Cài đặt không tồn tại cho người dùng này.'], 404);
        }
        
        $settings->update([
            'background_story_id' => $request->has('background_story_id') 
                ? $request->background_story_id 
                : $settings->background_story_id,
            'background_mode' => $request->background_mode ?? $settings->background_mode,
            'screen_mode' => $request->screen_mode ?? $settings->screen_mode, // ✅ Update screen_mode
            'font_family' => $request->font_family ?? $settings->font_family,
            'font_size' => $request->font_size ?? $settings->font_size,
            'line_height' => $request->line_height ?? $settings->line_height,
            'hasSettings' => true,
            'brightness' => $request->brightness ?? $settings->brightness,
            'mode' => $request->mode ?? $settings->mode,
            'yellow_light_mode' => $request->has('yellow_light_mode') 
                ? $request->yellow_light_mode 
                : $settings->yellow_light_mode,
            'background_style' => $request->background_style ?? $settings->background_style,
            'background_color' => $request->background_color ?? $settings->background_color,
            'selected_gradient' => $request->selected_gradient ?? $settings->selected_gradient,
            'background_opacity' => $request->background_opacity ?? $settings->background_opacity,
            'custom_background_style' => $request->custom_background_style ?? $settings->custom_background_style,
            'custom_background_color' => $request->custom_background_color ?? $settings->custom_background_color,
            'custom_selected_gradient' => $request->custom_selected_gradient ?? $settings->custom_selected_gradient,
            'custom_background_opacity' => $request->custom_background_opacity ?? $settings->custom_background_opacity,
        ]);

        $settings->load('backgroundStory');

        return response()->json([
            'message' => 'Cài đặt đã được cập nhật thành công!',
            'settings' => $settings
        ], 200);
    }
}
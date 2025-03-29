<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Storage;

class UserNotificationController extends Controller
{

    public function getPageOptions()
    {
        $pageOptions = [
            ['value' => 'home', 'label' => 'home'],
            ['value' => 'maps', 'label' => 'maps'],
        ];

        return response()->json([
            'pageOptions' => $pageOptions
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'page' => 'required|in:home,maps',
        ]);

        $notification = UserNotification::create([
            'title' => $validated['title'],
            'content_path' => '',
            'image_paths' => [],
            'page' => $validated['page'],
        ]);

        $notificationDir = 'notifications/' . $notification->id;
        $contentPath = $notificationDir . '/1.txt';
        Storage::disk('public')->put($contentPath, $validated['content']);

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $imagePath = $image->storeAs($notificationDir, ($index + 1) . '.' . $image->extension(), 'public');
                $imagePaths[] = $imagePath;
            }
        }

        $notification->update([
            'content_path' => $contentPath,
            'image_paths' => $imagePaths,
        ]);

        return response()->json($notification, 201);
    }


    public function index()
    {
        $notifications = UserNotification::all();

        return response()->json($notifications);
    }

    public function show($id)
    {
        $notification = UserNotification::find($id);
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        $content = Storage::disk('public')->get($notification->content_path);

        return response()->json([
            'id' => $notification->id,
            'title' => $notification->title,
            'content' => $content,
            'image_paths' => $notification->image_paths,
            'page' => $notification->page,
        ]);
    }
    public function updateText(Request $request, $id)
    {
        $notification = UserNotification::find($id);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'page' => 'nullable|in:home,maps',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Cập nhật tiêu đề và nội dung văn bản
        $notification->title = $validated['title'];
        Storage::disk('public')->put($notification->content_path, $validated['content']);

        // Cập nhật trang nếu cần
        if (isset($validated['page']) && $validated['page'] !== $notification->page) {
            $notification->page = $validated['page'];
        }

        // Xử lý cập nhật hình ảnh
        if ($request->hasFile('images')) {
            // Xoá hình ảnh cũ nếu có
            if (!empty($notification->image_paths)) {
                foreach ($notification->image_paths as $oldImage) {
                    if (Storage::disk('public')->exists($oldImage)) {
                        Storage::disk('public')->delete($oldImage);
                    }
                }
            }

            $imagePaths = [];
            $notificationDir = 'notifications/' . $notification->id;

            foreach ($request->file('images') as $index => $image) {
                $imagePath = $image->storeAs($notificationDir, ($index + 1) . '.' . $image->extension(), 'public');
                $imagePaths[] = $imagePath;
            }

            $notification->image_paths = $imagePaths;
        }

        $notification->save();

        return response()->json([
            'message' => 'Cập nhật thành công!',
            'notification' => $notification
        ], 200);
    }

    public function destroy($id)
    {
        $notification = UserNotification::findOrFail($id);
        Storage::disk('public')->delete($notification->content_path);
        Storage::disk('public')->delete($notification->image_paths);
        $notificationDir = 'notifications/' . $notification->id;
        Storage::disk('public')->deleteDirectory($notificationDir);
        $notification->delete();

        return response()->json(null, 204);
    }
}

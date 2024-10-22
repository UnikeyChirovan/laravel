<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Storage;

class UserNotificationController extends Controller
{
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Tạo bản ghi mới trong bảng user_notifications
        $notification = UserNotification::create([
            'title' => $validated['title'],
            'content_path' => '',
            'image_paths' => [],
        ]);

        // Tạo thư mục cho thông báo dựa vào ID
        $notificationDir = 'notifications/' . $notification->id;

        // Lưu file txt cho nội dung thông báo
        $contentPath = $notificationDir . '/1.txt';
        Storage::disk('public')->put($contentPath, $validated['content']);

        // Lưu hình ảnh nếu có
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $imagePath = $image->storeAs($notificationDir, ($index + 1) . '.' . $image->extension(), 'public');
                $imagePaths[] = $imagePath;
            }
        }

        // Cập nhật đường dẫn file content và hình ảnh vào bản ghi
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
        // Tìm thông báo dựa vào id
        $notification = UserNotification::find($id);

        // Kiểm tra nếu thông báo không tồn tại
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        // Đọc nội dung file txt
        $content = Storage::disk('public')->get($notification->content_path);

        // Trả về dữ liệu thông báo bao gồm tiêu đề, nội dung và đường dẫn hình ảnh
        return response()->json([
            'id' => $notification->id,
            'title' => $notification->title,
            'content' => $content,
            'image_paths' => $notification->image_paths,
        ]);
    }
    public function updateText(Request $request, $id)
    {
        $notification = UserNotification::find($id);
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        if ($request->has('title') && $request->has('content')) {
            $validatedText = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
            ]);
            $notification->title = $validatedText['title'];
            $contentPath = $notification->content_path;
            Storage::disk('public')->put($contentPath, $validatedText['content']);
            $notification->save();
        }
        return response()->json($notification, 200);
    }

    public function destroy($id)
    {
        // Tìm thông báo cần xóa
        $notification = UserNotification::findOrFail($id);

        // Xóa file txt và hình ảnh khỏi storage
        Storage::disk('public')->delete($notification->content_path);
        Storage::disk('public')->delete($notification->image_paths);

        // Xóa thư mục chứa dữ liệu của thông báo nếu không còn file
        $notificationDir = 'notifications/' . $notification->id;
        Storage::disk('public')->deleteDirectory($notificationDir);

        // Xóa thông báo khỏi cơ sở dữ liệu
        $notification->delete();

        return response()->json(null, 204);
    }
}

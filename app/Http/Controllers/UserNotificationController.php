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
        $lastUpdated = UserNotification::max('updated_at'); 

        return response()->json([
            'notifications' => $notifications,
            'last_updated' => $lastUpdated,
        ]);
    }

    public function show($id)
    {
        $notification = UserNotification::find($id);
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        $content = Storage::disk('public')->get($notification->content_path);

        return response()->json([
            'notification_detail' => [
                'id' => $notification->id,
                'title' => $notification->title,
                'content' => $content,
                'image_paths' => $notification->image_paths,
                'page' => $notification->page, 
            ],
            'last_updated' => $notification->updated_at,
        ]);
    }
    public function updateText(Request $request, $id)
    {
        $notification = UserNotification::find($id);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $validatedText = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'page' => 'nullable|in:home,maps', 
        ]);

        $notification->title = $validatedText['title'];
        Storage::disk('public')->put($notification->content_path, $validatedText['content']);
        if (isset($validatedText['page']) && $validatedText['page'] !== $notification->page) {
            $notification->page = $validatedText['page'];
        }

        $notification->save();

        return response()->json($notification, 200);
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

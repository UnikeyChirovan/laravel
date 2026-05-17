<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserNotification;
use App\Models\NotificationVote;
use App\Models\UserVoteResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

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
            'content' => 'required_if:type,normal|string|nullable',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'page' => 'required|in:home,maps',
            'type' => 'required|in:normal,voting',
            // Voting fields
            'voting_question' => 'required_if:type,voting|string|max:500',
            'voting_options' => 'required_if:type,voting|array|min:2|max:10',
            'voting_options.*.text' => 'required|string|max:200',
        ]);

        $notification = UserNotification::create([
            'title' => $validated['title'],
            'content_path' => '',
            'image_paths' => [],
            'page' => $validated['page'],
            'type' => $validated['type'],
        ]);

        $notificationDir = 'notifications/' . $notification->id;

        // Handle normal notification
        if ($validated['type'] === 'normal') {
            $contentPath = $notificationDir . '/1.txt';
            Storage::disk('public')->put($contentPath, $validated['content']);
            $notification->content_path = $contentPath;
        }

        // Handle voting notification
        if ($validated['type'] === 'voting') {
            $options = array_map(function($option, $index) {
                return [
                    'id' => $index + 1,
                    'text' => $option['text']
                ];
            }, $validated['voting_options'], array_keys($validated['voting_options']));

            NotificationVote::create([
                'notification_id' => $notification->id,
                'question' => $validated['voting_question'],
                'options' => $options
            ]);
        }

        // Handle images
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $imagePath = $image->storeAs($notificationDir, ($index + 1) . '.' . $image->extension(), 'public');
                $imagePaths[] = $imagePath;
            }
        }

        $notification->update(['image_paths' => $imagePaths]);

        return response()->json($notification->load('vote'), 201);
    }

    public function index()
    {
        $notifications = UserNotification::with('vote')->get();
        return response()->json($notifications);
    }

    public function show($id)
    {
        $notification = UserNotification::with('vote')->find($id);
        
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $response = [
            'id' => $notification->id,
            'title' => $notification->title,
            'image_paths' => $notification->image_paths,
            'page' => $notification->page,
            'type' => $notification->type,
        ];

        if ($notification->type === 'normal') {
            $content = Storage::disk('public')->get($notification->content_path);
            $response['content'] = $content;
        } elseif ($notification->type === 'voting' && $notification->vote) {
            $response['vote'] = [
                'id' => $notification->vote->id,
                'question' => $notification->vote->question,
                'options' => $notification->vote->options,
            ];
        }

        return response()->json($response);
    }

    public function getVoteResults($notificationId)
    {
        $notification = UserNotification::with('vote.responses')->find($notificationId);
        
        if (!$notification || $notification->type !== 'voting') {
            return response()->json(['message' => 'Voting notification not found'], 404);
        }

        $userId = Auth::id();
        $userVote = null;

        if ($userId) {
            $userResponse = UserVoteResponse::where('notification_vote_id', $notification->vote->id)
                ->where('user_id', $userId)
                ->first();
            
            if ($userResponse) {
                $userVote = $userResponse->option_id;
            }
        }

        return response()->json([
            'results' => $notification->vote->results,
            'user_vote' => $userVote,
            'total_votes' => $notification->vote->responses()->count()
        ]);
    }

    public function submitVote(Request $request, $notificationId)
    {
        $validated = $request->validate([
            'option_id' => 'required|integer|min:1'
        ]);

        $notification = UserNotification::with('vote')->find($notificationId);
        
        if (!$notification || $notification->type !== 'voting') {
            return response()->json(['message' => 'Voting notification not found'], 404);
        }

        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Check if option exists
        $validOption = collect($notification->vote->options)
            ->firstWhere('id', $validated['option_id']);
        
        if (!$validOption) {
            return response()->json(['message' => 'Invalid option'], 400);
        }

        // Check if user already voted
        $existingVote = UserVoteResponse::where('notification_vote_id', $notification->vote->id)
            ->where('user_id', $userId)
            ->first();

        if ($existingVote) {
            return response()->json(['message' => 'You have already voted'], 400);
        }

        // Create vote
        UserVoteResponse::create([
            'notification_vote_id' => $notification->vote->id,
            'user_id' => $userId,
            'option_id' => $validated['option_id']
        ]);

        return response()->json([
            'message' => 'Vote submitted successfully',
            'results' => $notification->vote->fresh()->results,
            'user_vote' => $validated['option_id'],
            'total_votes' => $notification->vote->responses()->count()
        ], 200);
    }

    public function updateText(Request $request, $id)
    {
        $notification = UserNotification::find($id);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required_if:type,normal|string|nullable',
            'page' => 'nullable|in:home,maps',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'type' => 'nullable|in:normal,voting',
            'voting_question' => 'required_if:type,voting|string|max:500',
            'voting_options' => 'required_if:type,voting|array|min:2|max:10',
            'voting_options.*.text' => 'required|string|max:200',
        ]);

        $notification->title = $validated['title'];

        if (isset($validated['type']) && $validated['type'] !== $notification->type) {
            $notification->type = $validated['type'];
        }

        if ($notification->type === 'normal' && isset($validated['content'])) {
            Storage::disk('public')->put($notification->content_path, $validated['content']);
        }

        if ($notification->type === 'voting' && isset($validated['voting_question'])) {
            $options = array_map(function($option, $index) {
                return [
                    'id' => $index + 1,
                    'text' => $option['text']
                ];
            }, $validated['voting_options'], array_keys($validated['voting_options']));

            if ($notification->vote) {
                $notification->vote()->update([
                    'question' => $validated['voting_question'],
                    'options' => $options
                ]);
            } else {
                NotificationVote::create([
                    'notification_id' => $notification->id,
                    'question' => $validated['voting_question'],
                    'options' => $options
                ]);
            }
        }

        if (isset($validated['page']) && $validated['page'] !== $notification->page) {
            $notification->page = $validated['page'];
        }

        if ($request->hasFile('images')) {
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
            'notification' => $notification->load('vote')
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
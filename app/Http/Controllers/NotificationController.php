<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
    public function create(Request $request)
    {
        $notification = Notification::create([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        $this->sendNotificationToSubscribers($notification);

        return response()->json(['success' => 'Thông báo đã được tạo và gửi!']);
    }

        private function sendNotificationToSubscribers($notification)
        {
            $subscribers = NewsletterSubscription::all();

            foreach ($subscribers as $subscriber) {
                Mail::send('emails.notification', ['notification' => $notification, 'subscriber' => $subscriber], function ($message) use ($subscriber, $notification) {
                    $message->to($subscriber->email)
                            ->subject($notification->title);
                });
            }
        }

        public function getAll(Request $request)
        {
            $notifications = Notification::all();
            return response()->json($notifications);
        }

        public function delete($id)
        {
            $notification = Notification::find($id);
            if (!$notification) {
                return response()->json(['error' => 'Thông báo không tồn tại!'], 404);
            }

            $notification->delete();
            return response()->json(['success' => 'Thông báo đã được xóa!']);
        }

}
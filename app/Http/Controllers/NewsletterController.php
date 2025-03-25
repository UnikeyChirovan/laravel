<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\NewsletterSubscription;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:newsletter_subscriptions,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Email không hợp lệ hoặc đã tồn tại!'], 400);
        }
        NewsletterSubscription::create(['email' => $request->email]);
        Mail::send('emails.newsletter_subscription', ['email' => $request->email], function($message) use ($request) {
            $message->to($request->email);
            $message->subject('Đăng ký nhận tin thành công!');
        });

        return response()->json(['success' => 'Đăng ký nhận tin thành công!'], 200);
    }
    public function unsubscribe(Request $request)
    {
        $email = $request->query('email');

        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email|exists:newsletter_subscriptions,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Email không tồn tại trong hệ thống!'], 400);
        }
        NewsletterSubscription::where('email', $email)->delete();

        Mail::send('emails.newsletter_unsubscription', ['email' => $email], function($message) use ($email) {
            $message->to($email);
            $message->subject('Hủy đăng ký thành công!');
        });

        return response()->json(['success' => 'Hủy đăng ký thành công!'], 200);
    }

    public function getEmails()
    {
        $emails = NewsletterSubscription::select('email', 'created_at')->get();
        return response()->json(['emails' => $emails], 200);
    }


}

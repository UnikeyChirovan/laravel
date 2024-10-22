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

        // Lưu email vào database
        NewsletterSubscription::create(['email' => $request->email]);

        // Gửi email thông báo
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

    // Xóa email khỏi database
    NewsletterSubscription::where('email', $email)->delete();

    // Gửi email xác nhận hủy đăng ký thành công
    Mail::send('emails.newsletter_unsubscription', ['email' => $email], function($message) use ($email) {
        $message->to($email);
        $message->subject('Hủy đăng ký thành công!');
    });

    return response()->json(['success' => 'Hủy đăng ký thành công!'], 200);
}


}

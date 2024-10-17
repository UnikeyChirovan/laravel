<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\EmailVerification;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user->only([
            'id',
            'name',
            'username',
            'avatar',
            'email',
            'cover',
            'occupation',
            'birthday',
            'gender',
            'address',
            'biography',
            'hobbies',
            'phone_number',
            'avatar_position', 
            'cover_position', 
        ]));
    }

    public function edit($id)
    {
        $users = User::find($id);
        $genders = [
            ['value' => 'Nam', 'label' => 'Nam'],
            ['value' => 'Nữ', 'label' => 'Nữ']
         ];

        return response()->json([
            "users" => $users,
            "genders" => $genders
        ]);
    }

    public function update(Request $request, $id)
    {
        $token = $request->bearerToken();

        if ($token) {
            try {
                $payload = JWTAuth::setToken($token)->getPayload();
                $userID = $payload->get('id');
                $isAdmin = $payload->get('isAdmin');

                if ($userID != $id && !$isAdmin) {
                    return response()->json(['message' => 'Không được phép cập nhật thông tin người dùng khác!'], 403);
                }
            } catch (\Exception $e) {
                return response()->json(['message' => 'Cập nhật người dùng không hợp lệ!'], 403);
            }
        } else {
            return response()->json(['message' => 'Token không hợp lệ!'], 403);
        }

        $validated = $request->validate([
            "username" => "required|unique:users,username," . $id,
            "name" => "required|max:255",
            "nickname" => "required|max:255",
            "email" => "required|email",
            "occupation" => "nullable|string|max:255",
            "birthday" => "nullable|date",
            "gender" => "nullable|in:Nam,Nữ",
            "address" => "nullable|string|max:100",
            "biography" => "nullable|string",
            "hobbies" => "nullable|string",
            "phone_number" => "nullable|string|max:20",
        ], [
            "username.required" => "Nhập Tên Tài khoản",
            "username.unique" => "Tên Tài khoản đã tồn tại",
            "name.required" => "Nhập họ tên của bạn",
            "nickname.required" => "Nhập Tên muốn hiển thị",
            "name.max" => "Ký tự tối đa là 255",
            "nickname.max" => "Ký tự tối đa là 255",
            "email.required" => "Nhập Email",
            "email.email" => "Email không hợp lệ",
        ]);

        $user = User::find($id);
        if ($user->email !== $request["email"]) {
            $user->status_id = 5;
            $user->email = $request["email"];
            $user->save();
            $verificationToken = Str::random(64);
            EmailVerification::create([
                'user_id' => $user->id,
                'token' => $verificationToken,
            ]);

            $verificationUrl = url('/api/auth/verify-email?token=' . $verificationToken);
            try {
                Mail::send('emails.verify', ['url' => $verificationUrl, 'user' => $user], function ($message) use ($user) {
                    $message->to($user->email);
                    $message->subject('Xác thực địa chỉ email của bạn');
                });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Không thể gửi email xác thực!'], 500);
            }
        }
        $user->update([
            "username" => $request["username"],
            "name" => $request["name"],
            "nickname" => $request["nickname"],
            "occupation" => $request["occupation"],
            "birthday" => $request["birthday"],
            "gender" => $request["gender"],
            "address" => $request["address"],
            "biography" => $request["biography"],
            "hobbies" => $request["hobbies"],
            "phone_number" => $request["phone_number"],
        ]);
        if ($request["change_password"] == true) {
            $validated = $request->validate([
                "password" => "required|confirmed"
            ], [
                "password.required" => "Nhập Mật khẩu",
                "password.confirmed" => "Mật khẩu và Xác nhận mật khẩu không khớp"
            ]);

            $user->update([
                "password" => Hash::make($request["password"]),
                "change_password_at" => now()
            ]);
        }

        return response()->json(['message' => 'Cập nhật thông tin thành công!'], 200);
    }


    public function updatePosition(Request $request, $id)
    {
        $token = $request->bearerToken();

        if ($token) {
            try {
                $payload = JWTAuth::setToken($token)->getPayload();
                $userID = $payload->get('id');
                $isAdmin = $payload->get('isAdmin');
                if ($userID != $id && !$isAdmin) {
                    return response()->json(['message' => 'Không được phép cập nhật thông tin người dùng khác!'], 403);
                }
            } catch (\Exception $e) {
                return response()->json(['message' => 'Cập nhật người dùng không hợp lệ!'], 403);
            }
        } else {
            return response()->json(['message' => 'Token không hợp lệ!'], 403);
        }
        $validator = Validator::make($request->all(), [
            'avatar_position' => 'nullable|integer',
            'cover_position' => 'nullable|integer',
        ], [
            'avatar_position.integer' => 'Vị trí avatar phải là số nguyên.',
            'cover_position.integer' => 'Vị trí cover phải là số nguyên.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $user = User::findOrFail($id);
        $user->avatar_position = $request->input('avatar_position', $user->avatar_position);
        $user->cover_position = $request->input('cover_position', $user->cover_position);
        $user->save();
        return response()->json(['message' => 'Vị trí avatar và cover đã được cập nhật thành công!'], 200);
    }

}

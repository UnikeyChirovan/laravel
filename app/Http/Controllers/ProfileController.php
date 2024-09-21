<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\BlacklistedIp;
use App\Models\DeviceManager;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show($id)
        {
            $user = User::findOrFail($id);
            return response()->json($user->only([
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
            ]));
        }
    public function create()
        {
            $genders = [
                ['value' => 'Nam', 'label' => 'Nam'],
                ['value' => 'Nữ', 'label' => 'Nữ']
            ];

            return response()->json([
                "genders" => $genders
            ]);
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
        // Lấy token từ request
        $token = $request->bearerToken();
        
        if ($token) {
            try {
                // Lấy payload từ token
                $payload = JWTAuth::setToken($token)->getPayload();
                // Lấy userID từ payload
                $userID = $payload->get('id');
                $isAdmin = $payload->get('isAdmin');

                // So sánh userID với $id được gửi lên
                if ($userID != $id && !$isAdmin) {
                    return response()->json(['message' => 'Không được phép cập nhật thông tin người dùng khác!'], 403);
                }
            } catch (\Exception $e) {
                return response()->json(['message' => 'Cập nhật người dùng không hợp lệ!'], 403);
            }
        } else {
            return response()->json(['message' => 'Token không hợp lệ!'], 403);
        }

        // Xác thực các dữ liệu gửi lên
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
            "phone_number" => "nullable|string|max:20"
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

        // Cập nhật các trường vào cơ sở dữ liệu
        User::find($id)->update([
            "username" => $request["username"],
            "name" => $request["name"],
            "nickname" => $request["nickname"],
            "email" => $request["email"],
            "occupation" => $request["occupation"],
            "birthday" => $request["birthday"],
            "gender" => $request["gender"],
            "address" => $request["address"],
            "biography" => $request["biography"],
            "hobbies" => $request["hobbies"],
            "phone_number" => $request["phone_number"],
        ]);

        // Kiểm tra nếu có thay đổi mật khẩu
        if ($request["change_password"] == true) {
            $validated = $request->validate([
                "password" => "required|confirmed"
            ], [
                "password.required" => "Nhập Mật khẩu",
                "password.confirmed" => "Mật khẩu và Xác nhận mật khẩu không khớp"
            ]);

            User::find($id)->update([
                "password" => Hash::make($request["password"]),
                "change_password_at" => NOW()
            ]);
        }

        return response()->json(['message' => 'Cập nhật thông tin thành công!'], 200);
    }



}

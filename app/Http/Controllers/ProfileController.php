<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\EmailVerification;
use Illuminate\Support\Facades\DB;
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

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'avatar' => $user->avatar,
            'cover' => $user->cover,
            'avatar_position' => $user->avatar_position,
            'cover_position' => $user->cover_position,
        ];

        if ($user->show_email) {
            $data['email'] = $user->email;
        }
        if ($user->show_phone_number) {
            $data['phone_number'] = $user->phone_number;
        }
        if ($user->show_occupation) {
            $data['occupation'] = $user->occupation;
        }
        if ($user->show_birthday) {
            $data['birthday'] = $user->birthday;
        }
        if ($user->show_gender) {
            $data['gender'] = $user->gender;
        }
        if ($user->show_address) {
            $data['address'] = $user->address;
        }
        if ($user->show_biography) {
            $data['biography'] = $user->biography;
        }
        if ($user->show_hobbies) {
            $data['hobbies'] = $user->hobbies;
        }

        return response()->json($data);
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
                return response()->json(['message' => 'Không thể gởi email xác thực!'], 500);
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


    public function getVisibilitySettings(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'email' => $user->show_email,
            'phone_number' => $user->show_phone_number,
            'occupation' => $user->show_occupation,
            'biography' => $user->show_biography,
            'hobbies' => $user->show_hobbies,
            'gender' => $user->show_gender,    
            'address' => $user->show_address,  
            'birthday' => $user->show_birthday 
        ]);
    }

    public function updateVisibilitySettings(Request $request)
    {
        $user = $request->user();
        $user->update([
            'show_email' => $request->email,
            'show_phone_number' => $request->phone_number,
            'show_occupation' => $request->occupation,
            'show_biography' => $request->biography,
            'show_hobbies' => $request->hobbies,
            'show_gender' => $request->gender,    
            'show_address' => $request->address,
            'show_birthday' => $request->birthday  
        ]);
        return response()->json(['message' => 'Cập nhật thành công']);
    }

    public function showGuest($id)
    {
        $user = User::findOrFail($id);
        $currentUser = auth()->user();

        // Kiểm tra chặn
        $isBlocked = DB::table('blocks')
            ->where(function ($q) use ($currentUser, $user) {
                $q->where('blocker_id', $currentUser->id)->where('blocked_id', $user->id);
            })
            ->orWhere(function ($q) use ($currentUser, $user) {
                $q->where('blocker_id', $user->id)->where('blocked_id', $currentUser->id);
            })
            ->exists();

        if ($isBlocked) {
            return response()->json(['message' => 'Không thể truy cập thông tin'], 403);
        }

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'avatar' => $user->avatar,
            'cover' => $user->cover,
            'avatar_position' => $user->avatar_position,
            'cover_position' => $user->cover_position,
        ];

        if ($user->show_email) {
            $data['email'] = $user->email;
        }
        if ($user->show_phone_number) {
            $data['phone_number'] = $user->phone_number;
        }
        if ($user->show_occupation) {
            $data['occupation'] = $user->occupation;
        }
        if ($user->show_birthday) {
            $data['birthday'] = $user->birthday;
        }
        if ($user->show_gender) {
            $data['gender'] = $user->gender;
        }
        if ($user->show_address) {
            $data['address'] = $user->address;
        }
        if ($user->show_biography) {
            $data['biography'] = $user->biography;
        }
        if ($user->show_hobbies) {
            $data['hobbies'] = $user->hobbies;
        }

        return response()->json($data);
    }
        public function getPrivacySettings(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'private_account' => $user->private_account,
            'allow_search' => $user->allow_search,
        ]);
    }

    public function updatePrivacySettings(Request $request)
    {
        $validated = $request->validate([
            'private_account' => 'required|boolean',
            'allow_search' => 'required|boolean',
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'message' => 'Cập nhật cài đặt quyền riêng tư thành công',
            'data' => [
                'private_account' => $user->private_account,
                'allow_search' => $user->allow_search,
            ]
        ]);
    }
}

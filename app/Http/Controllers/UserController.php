<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Block;
use App\Models\Follow;
use App\Models\RequestLog;
use Illuminate\Http\Request;
use App\Models\BlacklistedIp;
use App\Models\DeviceManager;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;
use App\Events\UserOnlineStatus;
use App\Models\EmailVerification;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function show($id)
    {
        return User::findOrFail($id);
    }

    public function index()
    {
        $users = User::where("users.id", "!=", "1")
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->join('users_status', 'users.status_id', '=', 'users_status.id')
            ->select(
                'users.*',
                'departments.name as departments',
                'users_status.name as status'
            )
            ->get();

        return response()->json($users);
    }

    public function create()
    {
        $users_status = DB::table("users_status")
            ->select(
                "id as value",
                "name as label"
            )
            ->get();
        $departments = DB::table("departments")
            ->select(
                "id as value",
                "name as label"
            )
            ->get();
        
        return response()->json([
            "users_status" => $users_status,
            "departments" => $departments
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "status_id" => "required",
            "username" => "required|unique:users,username",
            "name" => "required|max:255",
            "nickname" => "required|max:255",
            "email" => "required|email|unique:users,email",
            "department_id" => "required",
            "password" => "required|confirmed"
        ], [
            "status_id.required" => "Nhập Tình trạng",
            "username.required" => "Nhập Tên Tài khoản",
            "username.unique" => "Tên Tài khoản đã tồn tại",
            "name.required" => "Nhập Họ và Tên",
            "name.max" => "Ký tự tối đa là 255",
            "nickname.required" => "Nhập tên muốn hiển thị",
            "nickname.max" => "Ký tự tối đa là 255",
            "email.required" => "Nhập Email",
            "email.email" => "Email không hợp lệ",
            "email.unique" => "Email đã tồn tại",
            "department_id.required" => "Nhập Phòng ban",
            "password.required" => "Nhập Mật khẩu",
            "password.confirmed" => "Mật khẩu và Xác nhận mật khẩu không khớp"
        ]);

        // Tạo user
        $user = $request->except(["password", "password_confirmation"]);
        $user["password"] = Hash::make($request["password"]);
        $newUser = User::create($user);

        // Nếu status_id = 5 (chưa xác thực), gởi email xác nhận
        if ($request->status_id == 5) {
            // Xóa token cũ nếu có
            EmailVerification::where('user_id', $newUser->id)->delete();

            // Tạo token xác thực mới
            $verificationToken = Str::random(64);
            EmailVerification::create([
                'user_id' => $newUser->id,
                'token' => $verificationToken,
            ]);

            $verificationUrl = url('/api/auth/verify-email?token=' . $verificationToken);

            // gởi email xác thực
            try {
                Mail::send('emails.verify', ['url' => $verificationUrl, 'user' => $newUser], function ($message) use ($newUser) {
                    $message->to($newUser->email);
                    $message->subject('Xác thực tài khoản của bạn');
                });
            } catch (\Exception $e) {
                Log::error('Lỗi gởi email xác thực: ' . $e->getMessage());
                // Không xóa user, chỉ log lỗi
                return response()->json([
                    "message" => "Tạo tài khoản thành công nhưng không thể gởi email xác thực. Vui lòng liên lạc admin."
                ], 201);
            }

            return response()->json([
                "message" => "Tạo tài khoản thành công! Email xác thực đã được gởi đến " . $newUser->email
            ], 201);
        }

        return response()->json([
            "message" => "Tạo tài khoản thành công!"
        ], 201);
    }

    public function edit($id)
    {
        $users = User::find($id);

        $users_status = DB::table("users_status")
            ->select(
                "id as value",
                "name as label"
            )
            ->get();
        
        $departments = DB::table("departments")
            ->select(
                "id as value",
                "name as label"
            )
            ->get();

        return response()->json([
            "users" => $users,
            "users_status" => $users_status,
            "departments" => $departments
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            "status_id" => "required",
            "username" => "required|unique:users,username,".$id,
            "name" => "required|max:255",
            "nickname" => "required|max:255",
            "email" => "required|email",
            "department_id" => "required"
        ], [
            "status_id.required" => "Nhập Tình trạng",
            "username.required" => "Nhập Tên Tài khoản",
            "username.unique" => "Tên Tài khoản đã tồn tại",
            "name.required" => "Nhập Họ và Tên",
            "nickname.required" => "Nhập Tên muốn hiển thị",
            "name.max" => "Ký tự tối đa là 255",
            "nickname.max" => "Ký tự tối đa là 255",
            "email.required" => "Nhập Email",
            "email.email" => "Email không hợp lệ",
            "department_id.required" => "Nhập Phòng ban"
        ]);

        $user = User::find($id);
        $oldStatusId = $user->status_id;
        $oldEmail = $user->email;

        // Cập nhật thông tin user
        $user->update([
            "status_id" => $request["status_id"],
            "username" => $request["username"],
            "name" => $request["name"],
            "nickname" => $request["nickname"],
            "email" => $request["email"],
            "department_id" => $request["department_id"]
        ]);

        // Xử lý thay đổi mật khẩu
        if ($request["change_password"] == true) {
            $validated = $request->validate([
                "password" => "required|confirmed"
            ], [
                "password.required" => "Nhập Mật khẩu",
                "password.confirmed" => "Mật khẩu và Xác nhận mật khẩu không khớp"
            ]);

            $user->update([
                "password" => Hash::make($request["password"]),
                "change_password_at" => NOW()
            ]);
        }

        // Kiểm tra nếu status_id được chuyển thành 5 hoặc email thay đổi khi status_id = 5
        $statusChangedTo5 = ($oldStatusId != 5 && $request->status_id == 5);
        $emailChangedWithStatus5 = ($oldEmail != $request->email && $request->status_id == 5);

        if ($statusChangedTo5 || $emailChangedWithStatus5) {
            // Xóa token xác thực cũ
            EmailVerification::where('user_id', $user->id)->delete();

            // Tạo token xác thực mới
            $verificationToken = Str::random(64);
            EmailVerification::create([
                'user_id' => $user->id,
                'token' => $verificationToken,
            ]);

            $verificationUrl = url('/api/auth/verify-email?token=' . $verificationToken);

            // gởi email xác thực
            try {
                Mail::send('emails.verify', ['url' => $verificationUrl, 'user' => $user], function ($message) use ($user) {
                    $message->to($user->email);
                    $message->subject('Xác thực tài khoản của bạn');
                });

                return response()->json([
                    "message" => "Cập nhật thành công! Email xác thực đã được gởi đến " . $user->email
                ], 200);
            } catch (\Exception $e) {
                Log::error('Lỗi gởi email xác thực: ' . $e->getMessage());
                return response()->json([
                    "message" => "Cập nhật thành công nhưng không thể gởi email xác thực. Vui lòng thử lại sau."
                ], 200);
            }
        }

        return response()->json([
            "message" => "Cập nhật thành công!"
        ], 200);
    }

    public function destroy($id)
    {
        User::find($id)->delete();
    }

    // ... các methods khác giữ nguyên ...

    public function getAllDeviceInfo()
    {
        $deviceManagers = DeviceManager::all();
        return response()->json([
            'device_infos' => $deviceManagers,
        ]);
    }

    public function getAllBlacklist()
    {
        $blacklist = BlacklistedIp::all();
        return response()->json([
            'blacklist' => $blacklist,
        ]);
    }

    public function transferToBlacklist(Request $request, $userId)
    {
        $deviceManager = DeviceManager::where('user_id', $userId)->first();
        if (!$deviceManager) {
            return response()->json(['message' => 'Không tìm thấy thông tin thiết bị.'], 404);
        }
        $existingBlacklist = BlacklistedIp::where('user_id', $deviceManager->user_id)
                                        ->where('ip_address', $deviceManager->ip_address)
                                        ->where('user_agent', $deviceManager->user_agent)
                                        ->first();
        if ($existingBlacklist) {
            return response()->json(['message' => 'Thông tin đã có trong blacklist.'], 400);
        }
        $reason = $request->input('reason', 'Người dùng vi phạm chính sách.');
        
        BlacklistedIp::create([
            'user_id' => $deviceManager->user_id,
            'ip_address' => $deviceManager->ip_address,
            'user_agent' => $deviceManager->user_agent,
            'reason' => $reason,
        ]);
        $deviceManager->delete();

        return response()->json(['message' => 'Thông tin đã được chuyển vào blacklist.']);
    }

    public function deleteFromBlacklist($id)
    {
        $blacklist = BlacklistedIp::find($id);
        if (!$blacklist) {
            return response()->json(['message' => 'Không tìm thấy mục trong blacklist.'], 404);
        }

        $blacklist->delete();
        return response()->json(['message' => 'Xóa blacklist thành công.'], 204);
    }

    public function getAllRequestLogs()
    {
        $requestLogs = RequestLog::all();
        return response()->json(['request_logs' => $requestLogs]);
    }

    public function deleteRequestLog($id)
    {
        $requestLog = RequestLog::find($id);
        
        if (!$requestLog) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], 404);
        }

        $requestLog->delete();
        return response()->json(['message' => 'Xóa bản ghi thành công.']);
    }

    public function deleteAllRequestLogs()
    {
        RequestLog::truncate();
        return response()->json(['message' => 'Đã xóa tất cả bản ghi.']);
    }

    public function transferToBlacklistFromRequestLog(Request $request, $id)
    {
        $requestLog = RequestLog::find($id);

        if (!$requestLog) {
            return response()->json(['message' => 'Không tìm thấy bản ghi request log.'], 404);
        }
        $existingBlacklist = BlacklistedIp::where('ip_address', $requestLog->ip_address)
                                            ->where('user_agent', $requestLog->user_agent)
                                            ->first();
        
        if ($existingBlacklist) {
            return response()->json(['message' => 'Thông tin đã có trong blacklist.'], 400);
        }
        BlacklistedIp::create([
            'user_id' => null, 
            'ip_address' => $requestLog->ip_address,
            'user_agent' => $requestLog->user_agent,
            'reason' => 'Gọi nhiều request nghi ngờ hacker xâm nhập.',
        ]);
        $requestLog->delete();

        return response()->json(['message' => 'Thông tin đã được chuyển vào blacklist và xóa khỏi request logs.']);
    }

    public function explore(Request $request)
    {
        $userId = auth()->id();
        $cacheKey = "explore_limit:$userId";

        $remainingAttempts = Redis::get($cacheKey);
        if ($remainingAttempts !== null && $remainingAttempts <= 0) {
            return response()->json(['message' => 'Bạn đã hết lượt đổi, hãy đợi hồi phục!'], 429);
        }

        $followedUsers = Follow::where('follower_id', $userId)->pluck('following_id');

        $blockedUsers = Block::where('blocker_id', $userId)
            ->orWhere('blocked_id', $userId)
            ->get(['blocker_id', 'blocked_id'])
            ->flatMap(function ($block) {
                return [$block->blocker_id, $block->blocked_id];
            })
            ->unique()
            ->filter(fn($id) => $id !== $userId)
            ->values();

        $users = User::where('id', '!=', $userId)
            ->where('id', '!=', 1)
            ->whereNotIn('id', $followedUsers)
            ->whereNotIn('id', $blockedUsers)
            ->inRandomOrder()
            ->limit(5)
            ->get(['id', 'name', 'avatar']);

        if ($remainingAttempts === null) {
            Redis::setex($cacheKey, 10800, 2);
        } else {
            Redis::decr($cacheKey);
        }

        return response()->json([
            'users' => $users,
            'remainingAttempts' => Redis::get($cacheKey)
        ]);
    }

    public function getOnlineStatus($userId)
    {
        $user = User::findOrFail($userId);
        
        if (!$user->show_online_status) {
            return response()->json([
                'online' => false,
                'hidden' => true,
                'last_active' => null
            ]);
        }

        return response()->json([
            'online' => $user->is_online,
            'last_active' => $user->last_active_at,
            'hidden' => false
        ]);
    }

    public function getBulkOnlineStatus(Request $request)
    {
        $userIds = $request->input('user_ids', []);
        
        $users = User::whereIn('id', $userIds)
            ->select('id', 'is_online', 'last_active_at', 'show_online_status')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'online' => $user->show_online_status ? $user->is_online : false,
                    'last_active' => $user->show_online_status ? $user->last_active_at : null,
                    'hidden' => !$user->show_online_status
                ];
            });

        return response()->json(['users' => $users]);
    }

    public function setOnline(Request $request)
    {
        $user = $request->user();
        
        if ($user->show_online_status) {
            $user->update([
                'is_online' => true,
                'last_active_at' => now()
            ]);
            
            broadcast(new UserOnlineStatus($user->id, true))->toOthers();
            
            return response()->json([
                'message' => 'Online status set',
                'online' => true
            ]);
        }
        
        return response()->json([
            'message' => 'Online status hidden by user settings',
            'online' => false
        ]);
    }

    public function setOffline(Request $request)
    {
        $user = $request->user();
        
        $user->update(['is_online' => false]);
        
        if ($user->show_online_status) {
            broadcast(new UserOnlineStatus($user->id, false))->toOthers();
        }
        
        return response()->json([
            'message' => 'Offline status set',
            'online' => false
        ]);
    }
}
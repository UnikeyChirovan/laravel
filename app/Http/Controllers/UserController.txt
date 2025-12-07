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





class UserController extends Controller
{
    public function show( $id)
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
            -> select(
                "id as value",
                "name as label"
            )
            ->get();
        $departments = DB::table("departments")
            -> select(
                "id as value",
                "name as label"
            )
            ->get();
        
        return response() -> json([
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
            "email" => "required|email",
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

            "department_id.required" => "Nhập Phòng ban",
            "password.required" => "Nhập Mật khẩu",
            "password.confirmed" => "Mật khẩu và Xác nhận mật khẩu không khớp"
        ]);

        // Eloquent ORM (Lưu ý: Khai báo $fillable/ $guarded trong Models User)
        // Cách 1:
        // User::create([
        //     "status_id" => $request["status_id"],
        //     "username" => $request["username"],
        //     "name" => $request["name"],
        //     "email" => $request["email"],
        //     "department_id" => $request["department_id"],
        //     "password" => Hash::make($request["password"])
        // ]);
                // Cách 2: Dùng với Field + Requet số lượng lớn ->all(): lấy hết; except: loại trừ
        $user = $request->except(["password", "password_confirmation"]);
        $user["password"] = Hash::make($request["password"]);
        User::create($user);

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

        User::find($id)->update([
            "status_id" => $request["status_id"],
            "username" => $request["username"],
            "name" => $request["name"],
            "nickname" => $request["nickname"],
            "email" => $request["email"],
            "department_id" => $request["department_id"]
        ]);

        if($request["change_password"] == true)
        {
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
    }

    public function destroy($id)
    {
        User::find($id)->delete();
    }



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

    // Kiểm tra số lượt đổi danh sách
    $remainingAttempts = Redis::get($cacheKey);
    if ($remainingAttempts !== null && $remainingAttempts <= 0) {
        return response()->json(['message' => 'Bạn đã hết lượt đổi, hãy đợi hồi phục!'], 429);
    }

    // Lấy danh sách ID người dùng đã follow
    $followedUsers = Follow::where('follower_id', $userId)->pluck('following_id');

    // Lấy danh sách ID người dùng bị block hai chiều
    $blockedUsers = Block::where('blocker_id', $userId)
        ->orWhere('blocked_id', $userId)
        ->get(['blocker_id', 'blocked_id'])
        ->flatMap(function ($block) {
            return [$block->blocker_id, $block->blocked_id];
        })
        ->unique()
        ->filter(fn($id) => $id !== $userId) // Loại bỏ chính user
        ->values();

    // Lọc danh sách người dùng ngẫu nhiên
    $users = User::where('id', '!=', $userId)
        ->where('id', '!=', 1) // Bỏ admin có id = 1
        ->whereNotIn('id', $followedUsers) // Loại những người đã follow
        ->whereNotIn('id', $blockedUsers) // Loại những người bị block hai chiều
        ->inRandomOrder()
        ->limit(5)
        ->get(['id', 'name', 'avatar']);

    // Cập nhật số lượt đổi danh sách
    if ($remainingAttempts === null) {
        Redis::setex($cacheKey, 10800, 2); // 3 giờ TTL, 2 lần đổi còn lại
    } else {
        Redis::decr($cacheKey);
    }

    return response()->json([
        'users' => $users,
        'remainingAttempts' => Redis::get($cacheKey)
    ]);
}
}


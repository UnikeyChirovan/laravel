<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RequestLog;
use Illuminate\Http\Request;
use App\Models\BlacklistedIp;
use App\Models\DeviceManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;





class UserController extends Controller
{
    public function show( $id)
        {
            return User::findOrFail($id);
        }
public function index()
{
    // Ghi lại thời gian bắt đầu
    Log::info('Starting user fetch process.');

    // Thời gian bắt đầu
    $startTime = microtime(true);

    $users = User::where("users.id", "!=", "1")
        ->join('departments', 'users.department_id', '=', 'departments.id')
        ->join('users_status', 'users.status_id', '=', 'users_status.id')
        ->select(
            'users.*',
            'departments.name as departments',
            'users_status.name as status'
        )
        ->get();

    // Ghi lại thời gian kết thúc
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;

    // Ghi log thời gian thực hiện
    Log::info('User fetch process completed. Execution time: ' . $executionTime . ' seconds.');

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
        // Kiểm tra nếu có thông tin thiết bị
        $deviceManager = DeviceManager::where('user_id', $userId)->first();
        if (!$deviceManager) {
            return response()->json(['message' => 'Không tìm thấy thông tin thiết bị.'], 404);
        }

        // Kiểm tra nếu đã có trong blacklist
        $existingBlacklist = BlacklistedIp::where('user_id', $deviceManager->user_id)
                                        ->where('ip_address', $deviceManager->ip_address)
                                        ->where('user_agent', $deviceManager->user_agent)
                                        ->first();
        if ($existingBlacklist) {
            return response()->json(['message' => 'Thông tin đã có trong blacklist.'], 400);
        }

        // Lưu vào blacklist với lý do từ request
        $reason = $request->input('reason', 'Người dùng vi phạm chính sách.');
        
        BlacklistedIp::create([
            'user_id' => $deviceManager->user_id,
            'ip_address' => $deviceManager->ip_address,
            'user_agent' => $deviceManager->user_agent,
            'reason' => $reason,
        ]);

        // Xóa thông tin thiết bị
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

        // Xóa một request log theo ID
        public function deleteRequestLog($id)
        {
            $requestLog = RequestLog::find($id);
            
            if (!$requestLog) {
                return response()->json(['message' => 'Không tìm thấy bản ghi.'], 404);
            }

            $requestLog->delete();
            return response()->json(['message' => 'Xóa bản ghi thành công.']);
        }

        // Xóa tất cả request logs
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

            // Kiểm tra xem IP và User-Agent đã có trong blacklist chưa
            $existingBlacklist = BlacklistedIp::where('ip_address', $requestLog->ip_address)
                                                ->where('user_agent', $requestLog->user_agent)
                                                ->first();
            
            if ($existingBlacklist) {
                return response()->json(['message' => 'Thông tin đã có trong blacklist.'], 400);
            }

            // Thêm vào blacklist với lý do mặc định
            BlacklistedIp::create([
                'user_id' => null, // Có thể để null nếu không có user_id liên quan
                'ip_address' => $requestLog->ip_address,
                'user_agent' => $requestLog->user_agent,
                'reason' => 'Gọi nhiều request nghi ngờ hacker xâm nhập.',
            ]);

            // Xóa thông tin trong bảng request_logs sau khi chuyển
            $requestLog->delete();

            return response()->json(['message' => 'Thông tin đã được chuyển vào blacklist và xóa khỏi request logs.']);
        }

}
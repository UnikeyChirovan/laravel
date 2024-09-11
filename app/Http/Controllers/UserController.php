<?php

namespace App\Http\Controllers;

use queue;
use App\Models\User;
use App\Models\DeviceInfo;
use Illuminate\Http\Request;
use App\Models\BlacklistedIp;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cookie;





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
                'users_status.name as status')
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



        public function destroy($id){
            User::find($id)->delete();
        }


    protected function respondWithToken($token, $user, $isAdmin)
    {
        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'nickname'=>$user->nickname,
                'name'=>$user->name,
                'department_id' => $user->department_id,
                'status_id' => $user->status_id,
            ],
            'isAdmin' => $isAdmin,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60  // TTL của access token (giây)
        ]);
    }

    private function createAccessToken($user)
    {
        $isAdmin = $user->department_id == 1;
        $payload = [
            'isAdmin' => $isAdmin,  
            'id' => $user->id,
            'jti' => uniqid(),  
            'iat' => time(),   
        ];
        $token = JWTAuth::customClaims($payload)->fromUser($user);
        return $token;
    }

    private function createRefreshToken($user){
        $isAdmin = $user->department_id == 1;
        $deviceInfo = DeviceInfo::where('user_id', $user->id)
                                ->orderBy('created_at', 'desc')
                                ->first();
        if (!$deviceInfo) {
            return response()->json(["message" => "Không tìm thấy thông tin thiết bị"], 403);
        }
        $customClaims = [
            'sub' => $user->id, 
            'jti' => uniqid(), 
            'iat' => time(), 
            'exp' => time() + config('jwt.refresh_ttl') * 60,  
            'isAdmin' => $isAdmin,  
            'user_agent' => $deviceInfo->user_agent  
        ];
        $refreshToken = JWTAuth::customClaims($customClaims)->fromUser($user);
        return $refreshToken;
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            "username_or_email" => "required",
            "password" => "required"
        ], [
            "username_or_email.required" => "Nhập tài khoản hoặc email",
            "password.required" => "Nhập mật khẩu"
        ]);
        $user = User::where(function($query) use ($request) {
            $query->where("username", $request->username_or_email)
                ->orWhere("email", $request->username_or_email);
        })->first();
        if (!$user) {
            return response()->json(["message" => "Tài khoản hoặc mật khẩu không chính xác"], 401);
        }
        $deviceCount = DeviceInfo::where('user_id', $user->id)->count();
        if ($deviceCount >= 2) {
            return response()->json([
                "message" => "bạn đang đăng nhập trên nhiều thiết bị hoặc trình duyệt, vui lòng đăng xuất 1 tài khoản hoặc liên hệ admin"
            ], 403);
        }
        if ($user->status_id == 2) {
            return response()->json([
                "message" => "Bạn đang bị tạm khóa, vui lòng liên hệ admin"
            ], 403);
        }
        if ($user->status_id == 3) {
            $statusChangeTime = $user->updated_at; 
            $currentTime = now();
            if ($currentTime->diffInDays($statusChangeTime) < 3) {
                return response()->json([
                    "message" => "Tài khoản của bạn bị hạn chế đăng nhập trong 3 ngày kể từ lần thay đổi trạng thái cuối cùng."
                ], 403);
            }
        }
        if ($user->status_id == 4) {
            BlacklistedIp::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? 'unknown', 0, 255)
            ]);
            return response()->json([
                "message" => "Tài khoản của bạn đã bị đưa vào danh sách đen và không thể đăng nhập."
            ], 403);
        }
        if (Hash::check($request->password, $user->password)) {
            $isAdmin = $user->department_id == 1;
            $token = $this->createAccessToken($user);
            if (!$token) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $userAgent = substr($request->userAgent() ?? 'unknown', 0, 255);
            DeviceInfo::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $userAgent
                ]
            );
            $refreshToken = $this->createRefreshToken($user);
            $key = "refresh_tokens:" . $user->id . ":" . $userAgent;
            Redis::set($key, $refreshToken);
            $cookie = cookie('refresh_token', $refreshToken, 60 * 24 * 30, null, null, true, true, 'None');
            return $this->respondWithToken($token, $user, $isAdmin)->cookie($cookie);
        }
        return response()->json(["message" => "Tài khoản hoặc mật khẩu không chính xác"], 401);
    }

    public function getAllDeviceInfo()
    {
        $deviceInfos = DeviceInfo::all();
        return response()->json([
            'device_infos' => $deviceInfos,
        ]);
    }

    public function getAllBlacklist()
    {
        $blacklist = BlacklistedIp::all();
        return response()->json([
            'blacklist' => $blacklist,
        ]);
    }

    public function transferToBlacklist($userId)
    {
        $deviceInfo = DeviceInfo::where('user_id', $userId)->first();
        if (!$deviceInfo) {
            return response()->json(['message' => 'Không tìm thấy thông tin thiết bị.'], 404);
        }
        $existingBlacklist = BlacklistedIp::where('user_id', $deviceInfo->user_id)
                                            ->where('ip_address', $deviceInfo->ip_address)
                                            ->where('user_agent', $deviceInfo->user_agent)
                                            ->first();
        if ($existingBlacklist) {
            return response()->json(['message' => 'Thông tin đã có trong blacklist.'], 400);
        }
        BlacklistedIp::create([
            'user_id' => $deviceInfo->user_id,
            'ip_address' => $deviceInfo->ip_address,
            'user_agent' => $deviceInfo->user_agent,
            'reason' => 'Người dùng vi phạm chính sách.',
        ]);
        $deviceInfo->delete();
        return response()->json(['message' => 'Thông tin đã được chuyển vào blacklist.']);
    }

    public function register(Request $request)
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
            "username.required" => "Nhập Tên Tài khoản",
            "username.unique" => "Tên Tài khoản đã tồn tại",
            "name.required" => "Nhập Họ và Tên",
            "name.max" => "Ký tự tối đa là 255",
            "nickname.required" => "Nhập tên muốn hiển thị",
            "nickname.max" => "Ký tự tối đa là 255",
            "email.required" => "Nhập Email",
            "email.email" => "Email không hợp lệ",
            "password.required" => "Nhập Mật khẩu",
            "password.confirmed" => "Mật khẩu và Xác nhận mật khẩu không khớp"
        ]);

        // Tạo User
        $user = User::create([
            "status_id" => $validated["status_id"],
            "username" => $validated["username"],
            "name" => $validated["name"],
            "nickname" => $validated["nickname"],
            "email" => $validated["email"],
            "department_id" => $validated["department_id"],
            "password" => Hash::make($validated["password"])
        ]);
        DeviceInfo::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? 'unknown', 0, 255)
        ]);
        return response()->json([
            "message" => "Bạn đã đăng ký thành công!"
        ], 200);
    }

    public function refreshToken(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');
        if (!$refreshToken) {
            return response()->json(['error' => 'Refresh token missing'], 401);
        }
        try {
            $payload = JWTAuth::setToken($refreshToken)->getPayload();
            $userId = $payload->get('sub');
            $tokenUserAgent = $payload->get('user_agent');
            $user = User::find($userId);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }
            $key = "refresh_tokens:" . $userId . ":" . $tokenUserAgent;
            $storedToken = Redis::get($key);
            if ($storedToken !== $refreshToken) {
                return response()->json(['error' => 'Lỗi không xác định!'], 401);
            }
            Redis::del($key);
            $newAccessToken =  $this->createAccessToken($user);
            $newRefreshToken = $this->createRefreshToken($user);
            Redis::set($key, $newRefreshToken);
            $cookie = cookie('refresh_token', $newRefreshToken, 60 * 24 * 30, null, null, true, true, 'None');
            return response()->json([
                'access_token' => $newAccessToken,
                'token_type' => 'bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
            ])->cookie($cookie);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid refresh token'], 401);
        }
    }


    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token || !Auth::guard('api')->check()) {
            return response()->json(['message' => 'Token không hợp lệ hoặc User không được xác thực'], 401);
        }
        $user = Auth::guard('api')->user();
        $refreshToken = $request->cookie('refresh_token');
        if ($refreshToken) {
            $payload = JWTAuth::setToken($refreshToken)->getPayload();
            $tokenUserAgent = $payload->get('user_agent');
            $key = "refresh_tokens:" . $user->id . ":" . $tokenUserAgent;
            Redis::del($key);
        }
        DeviceInfo::where('user_id', $user->id)
            ->where('user_agent', substr($request->userAgent() ?? 'unknown', 0, 255))
            ->delete();
        $cookie = cookie('refresh_token', '', -1);
        return response()->json(['message' => 'Đăng xuất thành công'])->cookie($cookie);
    }

     public function checkSession(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');
        if (!$refreshToken) {
            return response()->json(['message' => 'Vui lòng đăng nhập!'], 200);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Phiên đăng nhập hợp lệ',
        ], 200);
    }
}
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

        
        // public function login(Request $request)
        // {
        //     $validated = $request->validate([
        //         "username_or_email" => "required",
        //         "password" => "required"
        //     ], [
        //         "username_or_email.required" => "Nhập tài khoản hoặc email",
        //         "password.required" => "Nhập mật khẩu"
        //     ]);

        //     $user = User::where(function($query) use ($request) {
        //                 $query->where("username", $request->username_or_email)
        //                     ->orWhere("email", $request->username_or_email);
        //             })->first();

        //     if ($user && Hash::check($request->password, $user->password)) {
        //         return response()->json([
        //             "message" => "Đăng nhập thành công",
        //             "user" => $user
        //         ]);
        //     }
        //     return response()->json([
        //         "message" => "Tài khoản hoặc mật khẩu không chính xác"
        //     ], 401);
        // }

        // new with access token

        // public function login(Request $request)
        // {
        // // Validate incoming request
        //     $validated = $request->validate([
        //         "username_or_email" => "required",
        //         "password" => "required"
        //     ], [
        //         "username_or_email.required" => "Nhập tài khoản hoặc email",
        //         "password.required" => "Nhập mật khẩu"
        //     ]);

        //     // Check user existence
        //     $user = User::where(function($query) use ($request) {
        //         $query->where("username", $request->username_or_email)
        //             ->orWhere("email", $request->username_or_email);
        //     })->first();

        //     // Verify user and password
        //     if ($user && Hash::check($request->password, $user->password)) {
        //         // Generate JWT token
        //         $token = JWTAuth::fromUser($user);

        //         // Return success response with JWT token
        //         return response()->json([
        //             "message" => "Đăng nhập thành công",
        //             "user" => $user,
        //             "token" => $token
        //         ]);
        //     }

        //     // Return error response if credentials are invalid
        //     return response()->json([
        //         "message" => "Tài khoản hoặc mật khẩu không chính xác"
        //     ], 401);
        // }

        // new with jwt
//         public function login(Request $request)
//         {
//             // Validate request
//             $validated = $request->validate([
//                 "username_or_email" => "required",
//                 "password" => "required"
//             ], [
//                 "username_or_email.required" => "Nhập tài khoản hoặc email",
//                 "password.required" => "Nhập mật khẩu"
//             ]);

//             // Tìm kiếm người dùng bằng username hoặc email
//             $user = User::where(function($query) use ($request) {
//                             $query->where("username", $request->username_or_email)
//                                 ->orWhere("email", $request->username_or_email);
//                         })->first();

//             // Kiểm tra mật khẩu
//             if ($user && Hash::check($request->password, $user->password)) {
//                 // Tạo JWT token dựa trên email và password của người dùng
//                 if (! $token = auth()->attempt(['email' => $user->email, 'password' => $request->password])) {
//                     return response()->json(['error' => 'Unauthorized'], 401);
//                 }

//                 // Tạo refresh token
//                 $refreshToken = $this->createRefreshToken();

//                 // Trả về thông tin người dùng và token
//                 return $this->respondWithToken($token, $refreshToken, $user);
//             }

//             // Trường hợp đăng nhập thất bại
//             return response()->json([
//                 "message" => "Tài khoản hoặc mật khẩu không chính xác"
//             ], 401);
//         }


//         protected function respondWithToken($token, $refreshToken, $user)
//     {
//         return response()->json([
//             'user' => $user,
//             'access_token' => $token,
//             'refresh_token' => $refreshToken,
//             'token_type' => 'bearer',
//             'expires_in' => auth()->factory()->getTTL() * 60
//         ]);
//     }

//     private function createRefreshToken(){
//          $data = [
//             'user_id' => auth('api')->user()->id,
//             'random' => rand() . time(),
//             'exp' => time() + config('jwt.refresh_ttl')
//         ];
//          $refreshToken = JWTAuth::getJWTProvider()->encode($data);
//          return $refreshToken;
//     }



        public function destroy($id){
            User::find($id)->delete();
        }

            // new all
        // public function login(Request $request)
        // {
        //     // Validate request
        //     $validated = $request->validate([
        //         "username_or_email" => "required",
        //         "password" => "required"
        //     ], [
        //         "username_or_email.required" => "Nhập tài khoản hoặc email",
        //         "password.required" => "Nhập mật khẩu"
        //     ]);

        //     $user = User::where(function($query) use ($request) {
        //         $query->where("username", $request->username_or_email)
        //             ->orWhere("email", $request->username_or_email);
        //     })->first();


        //     // kiểm tra status id



        //     if ($user && Hash::check($request->password, $user->password)) {

        //         // auth()->invalidate();
    
                
        //         // Tạo JWT token trường hợp không phải là admin hoặc là admin?
        //         if (!$token = Auth::guard('api')->attempt(['email' => $user->email, 'password' => $request->password])) {
        //             return response()->json(['error' => 'Unauthorized'], 401);
        //         }

        //         // Tạo refresh token
        //         $refreshToken = $this->createRefreshToken($user);

        //         // Lưu refresh token vào cookie
        //         $cookie = cookie('refresh_token', $refreshToken, 60 * 24 * 30, null, null, false, true);

        //         // Trả về thông tin người dùng và access token
        //         return $this->respondWithToken($token, $user)->cookie($cookie);
        //     }

        //     // Trường hợp đăng nhập thất bại
        //     return response()->json([
        //         "message" => "Tài khoản hoặc mật khẩu không chính xác"
        //     ], 401);
        // }

        // login with status_id and derpartment_id
//         public function login(Request $request)
// {
//     $validated = $request->validate([
//         "username_or_email" => "required",
//         "password" => "required"
//     ], [
//         "username_or_email.required" => "Nhập tài khoản hoặc email",
//         "password.required" => "Nhập mật khẩu"
//     ]);
//     $user = User::where(function($query) use ($request) {
//         $query->where("username", $request->username_or_email)
//             ->orWhere("email", $request->username_or_email);
//     })->first();
//     if (!$user) {
//         return response()->json(["message" => "Tài khoản hoặc mật khẩu không chính xác"], 401);
//     }
//     if ($user->status_id == 2) {
//         return response()->json([
//             "message" => "Bạn đang bị tạm khóa, vui lòng liên hệ admin"
//         ], 403);
//     }
//     if (Hash::check($request->password, $user->password)) {
//         $isAdmin = $user->department_id == 1;
//         $payload = [
//             'isAdmin' => $isAdmin,
//             'id' => $user->id
//         ];
//         $token = Auth::guard('api')->claims($payload)->attempt([
//             'email' => $user->email,
//             'password' => $request->password
//         ]);
//         if (!$token) {
//             return response()->json(['error' => 'Unauthorized'], 401);
//         }
//         $refreshToken = $this->createRefreshToken($user);
//         Redis::set($user->id, $refreshToken, 'EX', 60 * 24 * 30 * 60); // Expiry là 30 ngày
//         $cookie = cookie('refresh_token', $refreshToken, 60 * 24 * 30, null, null, false, true);
//         return $this->respondWithToken($token, $user)->cookie($cookie);
//     }
//     return response()->json(["message" => "Tài khoản hoặc mật khẩu không chính xác"], 401);
// }

// register with ip and user-agent:
public function register(Request $request)
{
    // Xác thực dữ liệu với các thông báo lỗi tùy chỉnh
    $request->validate([
        'avatar' => 'nullable|string',
        'username' => 'required|string|unique:users,username',
        'name' => 'required|string|max:255',
        'nickname' => 'required|string|max:255',
        'email' => 'required|string|email|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
    ], [
        'username.required' => 'Nhập Tên Tài khoản',
        'username.unique' => 'Tên Tài khoản đã tồn tại',

        'name.required' => 'Nhập Họ và Tên',
        'name.max' => 'Ký tự tối đa là 255',
               
        'nickname.required' => 'Nhập tên muốn hiển thị',
        'nickname.max' => 'Ký tự tối đa là 255',

        'email.required' => 'Nhập Email',
        'email.email' => 'Email không hợp lệ',
        'email.unique' => 'Email đã tồn tại',

        'password.required' => 'Nhập Mật khẩu',
        'password.confirmed' => 'Mật khẩu và Xác nhận mật khẩu không khớp',
    ]);

    // Tạo người dùng
    $user = User::create([
        'avatar' => $request->input('avatar'),
        'username' => $request->input('username'),
        'name' => $request->input('name'),
        'nickname' => $request->input('nickname'),
        'email' => $request->input('email'),
        'password' => Hash::make($request->input('password')),
        'login_at' => null,
        'change_password_at' => null,
    ]);

    // Lưu thông tin vào bảng device_infos
     DeviceInfo::create([
        'user_id' => $user->id,
        'ip_address' => $request->ip(),
        'user_agent' => substr($request->userAgent() ?? 'unknown', 0, 255),
    ]);

    // Trả về phản hồi
     return response()->json([
        'message' => 'Chào mừng bạn đến với Selorson Tales'
    ], 201);
}

// login with status id, department id , ip address, user agent

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

    if ($user->status_id == 2) {
        return response()->json([
            "message" => "Bạn đang bị tạm khóa, vui lòng liên hệ admin"
        ], 403);
    }

    // Kiểm tra điều kiện nếu status_id == 3
    if ($user->status_id == 3) {
        $statusChangeTime = $user->updated_at; // Thời điểm status_id thay đổi
        $currentTime = now();

        // Kiểm tra nếu đã qua 3 ngày kể từ khi status_id thay đổi
        if ($currentTime->diffInDays($statusChangeTime) < 3) {
            return response()->json([
                "message" => "Tài khoản của bạn bị hạn chế đăng nhập trong 3 ngày kể từ lần thay đổi trạng thái cuối cùng."
            ], 403);
        }
    }

    // Nếu status_id == 4, đưa thông tin vào blacklist
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


        // Tạo token và trả về response
        $isAdmin = $user->department_id == 1;
        $payload = [
            'isAdmin' => $isAdmin,
            'id' => $user->id
        ];
        $token = Auth::guard('api')->claims($payload)->attempt([
            'email' => $user->email,
            'password' => $request->password
        ]);

        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        DeviceInfo::updateOrCreate(
            [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? 'unknown', 0, 255)
            ]
        );


        $refreshToken = $this->createRefreshToken($user);
        Redis::set($user->id, $refreshToken, 'EX', 60 * 24 * 30 * 60); // Expiry là 30 ngày
        $cookie = cookie('refresh_token', $refreshToken, 60 * 24 * 30, null, null, false, true);

        return $this->respondWithToken($token, $user, $isAdmin)->cookie($cookie);
    }

    return response()->json(["message" => "Tài khoản hoặc mật khẩu không chính xác"], 401);
}






            public function refreshToken(Request $request)
            {
                $refreshToken = $request->cookie('refresh_token');
                if (!$refreshToken) {
                    return response()->json(['error' => 'Refresh token missing'], 401);
                }

                if (Redis::get('blacklist:refresh_token:' . $refreshToken)) {
                    return response()->json(['error' => 'Token has been blacklisted'], 401);
                }

                try {
                    // Giải mã refresh token để xác định người dùng
                    $payload = JWTAuth::setToken($refreshToken)->getPayload();
                    $userId = $payload->get('sub'); // Lấy user ID từ payload

                    // Xác định chính xác người dùng đang đăng nhập
                    if (!$user = Auth::guard('api')->loginUsingId($userId)) {
                        return response()->json(['error' => 'Invalid user'], 401);
                    }

                    // Lấy TTL còn lại của refresh token cũ
                    $ttl = $payload->get('exp') - time();

                    // Đưa refresh token cũ vào blacklist
                    Redis::setex('blacklist:refresh_token:' . $refreshToken, $ttl, true);

                    // Xóa refresh token cũ khỏi cookie
                    Cookie::queue(Cookie::forget('refresh_token'));

                    // Tạo mới access token và refresh token
                    $newAccessToken = Auth::guard('api')->refresh();
                    $newRefreshToken = $this->createRefreshToken($user);

                    // Lưu refresh token mới vào cookie
                   $cookie = cookie('refresh_token', $newRefreshToken, 60 * 24 * 30, null, null, false, true);

                    // Trả về access token mới
                    return response()->json([
                        'access_token' => $newAccessToken,
                        'token_type' => 'bearer',
                        'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
                    ])->cookie($cookie);
                } catch (\Exception $e) {
                    return response()->json(['error' => 'Invalid refresh token'], 401);
                }
            }


protected function respondWithToken($token, $user)
{
    return response()->json([
        'user' => $user,
        'isAdmin' => $user->department_id == 1 ? true : false,
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => Auth::guard('api')->factory()->getTTL() * 60  // TTL của access token
    ]);
}


private function createRefreshToken($user)
{
    // Xác định người dùng có phải là admin hay không dựa vào department_id
    $isAdmin = $user->department_id == 1;

    // Tạo các custom claims cho refresh token
    $customClaims = [
        'sub' => $user->id,  // ID người dùng
        'jti' => uniqid(),  // Unique ID cho token để tăng độ bảo mật
        'iat' => time(),  // Thời gian tạo token
        'exp' => time() + config('jwt.refresh_ttl') * 60,  // Thời gian hết hạn của refresh token
        'isAdmin' => $isAdmin  // Thêm isAdmin vào refresh token
    ];

    // Tạo refresh token với custom claims
    $refreshToken = JWTAuth::customClaims($customClaims)->fromUser($user);

    // Lưu refresh token vào Redis với key là refresh token và giá trị là ID người dùng
    Redis::set($refreshToken, $user->id, 'EX', config('jwt.refresh_ttl') * 60);

    return $refreshToken;
}

// UserController.php

public function getAllDeviceInfo()
{
    // Lấy toàn bộ thông tin từ bảng device_infos
    $deviceInfos = DeviceInfo::all();
    
    return response()->json([
        'device_infos' => $deviceInfos,
    ]);
}

public function getAllBlacklist()
{
    // Lấy toàn bộ thông tin từ bảng blacklisted_ips
    $blacklist = BlacklistedIp::all();
    
    return response()->json([
        'blacklist' => $blacklist,
    ]);
}
// UserController.php

public function transferToBlacklist($userId)
{
    // Tìm thông tin thiết bị của người dùng
    $deviceInfo = DeviceInfo::where('user_id', $userId)->first();

    if (!$deviceInfo) {
        return response()->json(['message' => 'Không tìm thấy thông tin thiết bị.'], 404);
    }

    // Kiểm tra xem thông tin đã có trong blacklist chưa
    $existingBlacklist = BlacklistedIp::where('user_id', $deviceInfo->user_id)
                                          ->where('ip_address', $deviceInfo->ip_address)
                                          ->where('user_agent', $deviceInfo->user_agent)
                                          ->first();

    if ($existingBlacklist) {
        return response()->json(['message' => 'Thông tin đã có trong blacklist.'], 400);
    }

    // Thêm thông tin vào blacklist
    BlacklistedIp::create([
        'user_id' => $deviceInfo->user_id,
        'ip_address' => $deviceInfo->ip_address,
        'user_agent' => $deviceInfo->user_agent,
        'reason' => 'Người dùng vi phạm chính sách.',
    ]);

    // Xóa thông tin thiết bị
    $deviceInfo->delete();

    return response()->json(['message' => 'Thông tin đã được chuyển vào blacklist.']);
}


}
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }


        $refreshToken = $this->createRefreshToken();

        return $this->respondWithToken($token, $refreshToken);
    }


    public function me()
    {   
        try{
            return response()->json(auth()->user());
        }catch (JWTException $exception ){
           return response()->json(['error' => 'Unauthorized'], 401);
        }
        
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        $refreshToken = request()->refresh_token;
        try{
            $decoded = JWTAuth::getJWTProvider()->encode($refreshToken);
            $user = User::find($decoded['user_id']);
            if(!$user){
                return response()->json(['error' => 'user not found'],404);
            }
            auth()->invalidate();
            $token = auth('api')->login($user);
            $refreshToken = $this->createRefreshToken();
             return $this->respondWithToken($token, $refreshToken);
        } catch (JWTException $exception ){
            return response()->json(['error' => 'Refresh Token Invalid'], 500);
        }
      
    }

    protected function respondWithToken($token, $refreshToken)
    {
        return response()->json([
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

     private function createRefreshToken(){
         $data = [
            'user_id' => auth('api')->user()->id,
            'random' => rand() . time(),
            'exp' => time() + config('jwt.refresh_ttl')
        ];
         $refreshToken = JWTAuth::getJWTProvider()->encode($data);
         return $refreshToken;
    }
}



////////////////////////////////

        public function login(Request $request)
{
    // Validate request
    $validated = $request->validate([
        "username_or_email" => "required",
        "password" => "required"
    ], [
        "username_or_email.required" => "Nhập tài khoản hoặc email",
        "password.required" => "Nhập mật khẩu"
    ]);

    // Truy vấn người dùng theo username hoặc email
    $user = User::where(function($query) use ($request) {
        $query->where("username", $request->username_or_email)
            ->orWhere("email", $request->username_or_email);
    })->first();

    // Kiểm tra người dùng có tồn tại và trạng thái của họ
    if (!$user) {
        return response()->json(["message" => "Tài khoản hoặc mật khẩu không chính xác"], 401);
    }

    // Kiểm tra status_id của người dùng
    if ($user->status_id == 2) { // Tạm khóa
        return response()->json([
            "message" => "Bạn đang bị tạm khóa, vui lòng liên hệ admin"
        ], 403); // Trả về mã lỗi 403 (Forbidden)
    }

    // Kiểm tra mật khẩu
    if (Hash::check($request->password, $user->password)) {
        // Phân loại người dùng theo department
        $isAdmin = $user->department_id == 1; // Giả sử admin có department_id = 1

        // Tạo JWT token với payload bổ sung isAdmin
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

        // Tạo refresh token
        $refreshToken = $this->createRefreshToken($user);

        // Lưu refresh token vào Redis
        Redis::set($refreshToken, $user->id, 'EX', 60 * 24 * 30 * 60); // Expiry là 30 ngày

        // Lưu refresh token vào cookie
        $cookie = cookie('refresh_token', $refreshToken, 60 * 24 * 30, null, null, false, true);

        // Trả về thông tin người dùng và access token
        return $this->respondWithToken($token, $user)->cookie($cookie);
    }

    // Trường hợp đăng nhập thất bại
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
        'user' => [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'isAdmin' => $user->department_id == 1 ? true : false,  // Kiểm tra người dùng có phải là admin
        ],
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


 }

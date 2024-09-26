<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DeviceInfo;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\BlacklistedIp;
use App\Models\DeviceManager;
use Illuminate\Support\Carbon;
use App\Models\EmailVerification;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;





class AuthController extends Controller
{
    protected function respondWithToken($token, $user, $isAdmin)
        {
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'nickname'=>$user->nickname,
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


    private function createRefreshToken($user)
        {
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
                'user_agent' => $deviceInfo->user_agent,
                'session_id' => $deviceInfo->session_id
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
            if ($user->status_id == 5) {
                return response()->json([
                    "message" => "Tài khoản chưa được xác thực, hãy kiểm tra lại email"
                ], 403);
            }
            if (Hash::check($request->password, $user->password)) {
                $isAdmin = $user->department_id == 1;
                $userAgent = substr($request->userAgent() ?? 'unknown', 0, 255); 
                $sessionId = uniqid("",false);  
                $token = $this->createAccessToken($user);
                if (!$token) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }
                $userAgent = substr($request->userAgent() ?? 'unknown', 0, 255);
                $sessionId = uniqid("",false);
                DeviceInfo::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_agent' => $userAgent,
                        'session_id' => $sessionId
                    ]
                );
                DeviceManager::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_agent' => $userAgent
                    ]
                );
                $refreshToken = $this->createRefreshToken($user);
                $key = "refresh_tokens:" . $user->id . ":" . $userAgent . ":" . $sessionId;
                $rememberMe = $request->rememberMe;
                $Expiration = $rememberMe ? 60 * 24 * 14 : 60 * 24;
                Redis::setex($key, $Expiration * 60, $refreshToken);
                $cookie = cookie('refresh_token', $refreshToken, $Expiration, null, null, true, true, 'None');
                return $this->respondWithToken($token, $user, $isAdmin)->cookie($cookie);
            }
            return response()->json(["message" => "Tài khoản hoặc mật khẩu không chính xác"], 401);
        }

    public function register(Request $request)
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
                "username.required" => "Nhập Tên Tài khoản",
                "username.unique" => "Tên Tài khoản đã tồn tại",
                "name.required" => "Nhập Họ và Tên",
                "name.max" => "Ký tự tối đa là 255",
                "nickname.required" => "Nhập tên muốn hiển thị",
                "nickname.max" => "Ký tự tối đa là 255",
                "email.required" => "Nhập Email",
                "email.email" => "Email không hợp lệ",
                "email.unique" => "Email đã tồn tại",
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

            // Lưu thông tin thiết bị
            DeviceManager::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? 'unknown', 0, 255)
            ]);

            // Tạo token xác thực email
            $verificationToken = Str::random(64);
            EmailVerification::create([
                'user_id' => $user->id,
                'token' => $verificationToken,
            ]);

            $verificationUrl = url('/api/auth/verify-email?token=' . $verificationToken);

            // Gửi email xác thực
            try {
                Mail::send('emails.verify', ['url' => $verificationUrl, 'user' => $user], function ($message) use ($user) {
                    $message->to($user->email);
                    $message->subject('Xác thực tài khoản của bạn');
                });
            } catch (\Exception $e) {
                // Nếu có lỗi trong quá trình gửi email, xóa người dùng đã tạo
                $user->delete();
                return response()->json([
                    "message" => "Đăng ký thất bại! Vui lòng thử lại."
                ], 500);
            }

            return response()->json([
                "message" => "Đăng ký thành công! Vui lòng kiểm tra email để xác thực tài khoản."
            ], 200);
        }


    public function refreshToken(Request $request)
        {
            $refreshToken = $request->cookie('refresh_token');
            $rememberMe = $request->rememberMe;
            $Expiration = $rememberMe ? 60 * 24 * 14 : 60 * 24;
            if (!$refreshToken) {
                return response()->json(['error' => 'Refresh token missing'], 403);
            }
            try {
                $payload = JWTAuth::setToken($refreshToken)->getPayload();
                $userId = $payload->get('sub');
                $tokenUserAgent = $payload->get('user_agent');
                $sessionId = $payload->get('session_id');
                $user = User::find($userId);
                if (!$user) {
                    return response()->json(['error' => 'User not found'], 403);
                }
                $key = "refresh_tokens:" . $userId . ":" . $tokenUserAgent . ":" . $sessionId;
                $storedToken = Redis::get($key);
                if ($storedToken !== $refreshToken) {
                    return response()->json(['error' => 'Lỗi không xác định!'], 403);
                }
                Redis::del($key);
                $newAccessToken =  $this->createAccessToken($user, $tokenUserAgent, $sessionId);
                $newRefreshToken = $this->createRefreshToken($user);
                Redis::setex($key, $Expiration, $newRefreshToken);
                $cookie = cookie('refresh_token', $newRefreshToken, $Expiration, null, null, true, true, 'None');
                return response()->json([
                    'access_token' => $newAccessToken,
                    'token_type' => 'bearer',
                    'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
                ])->cookie($cookie);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid refresh token'], 403);
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
                try {
                    $payload = JWTAuth::setToken($refreshToken)->getPayload();
                    $tokenUserAgent = $payload->get('user_agent');
                    $sessionId = $payload->get('session_id');
                    $key = "refresh_tokens:" . $user->id . ":" . $tokenUserAgent . ":" . $sessionId;
                    Redis::del($key);
                    DeviceInfo::where('user_id', $user->id)
                        ->where('user_agent', $tokenUserAgent)
                        ->where('session_id', $sessionId)
                        ->delete();
                } catch (\Exception $e) {
                    return response()->json(['message' => 'Refresh token không hợp lệ'], 401);
                }
            }
            $cookie = cookie('refresh_token', '', -1);
            return response()->json(['message' => 'Đăng xuất thành công'])->cookie($cookie);
        }


    public function forceLogout(Request $request)
        {
            $userId = $request->input('user_id');
            $userAgent = substr($request->userAgent() ?? 'unknown', 0, 255); 
            if (!$userId) {
                return response()->json(['message' => 'Thiếu user_id'], 400);
            }
            try {
                DeviceInfo::where('user_id', $userId)
                            ->where('user_agent', $userAgent)
                            ->delete();
                return response()->json(['message' => 'Vui lòng đăng nhập lại'], 200);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Lỗi khi xóa dữ liệu thiết bị: ' . $e->getMessage()], 500);
            }
        }
    public function verifyEmail(Request $request)
        {
            $token = $request->query('token');

            $verification = EmailVerification::where('token', $token)->first();

            if (!$verification) {
                return view('emails.verify-email', [
                'message' => 'Liên kết xác thực đã hết hạn!',
                'status' => 'error'
            ]);
            }

            $user = User::find($verification->user_id);

            if ($user) {
                $user->status_id = 1;
                $user->email_verified_at = now();
                $user->save();

                $verification->delete();

                return view('emails.verify-email', [
                    'message' => 'Xác thực tài khoản thành công, bạn có thể đăng nhập tài khoản!',
                    'status' => 'success'
                ]);
            }

            return response()->json(["message" => "Không tìm thấy người dùng."], 404);
        }
    
    public function sendResetLinkEmail(Request $request)
        {
            $request->validate(['email' => 'required|email']);
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['message' => 'Email không tồn tại.'], 404);
            }
            $token = Str::random(60);
            DB::table('password_resets')->updateOrInsert(
                ['email' => $user->email],
                [
                    'email' => $user->email,
                    'token' => Hash::make($token),
                    'created_at' => Carbon::now()
                ]
            );
            $resetUrl = url('http://127.0.0.1:8080/password-reset') . '?token=' . $token . '&email=' . urlencode($user->email);
            try {
                Mail::send('emails.password-reset', ['url' => $resetUrl, 'user' => $user], function ($message) use ($user) {
                    $message->to($user->email);
                    $message->subject('Đặt lại mật khẩu của bạn');
                });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Không thể gửi email. Vui lòng thử lại sau.'], 500);
            }
            return response()->json(['message' => 'Chúng tôi đã gửi đường dẫn đặt lại mật khẩu đến email của bạn.'], 200);
        }


    public function resetPassword(Request $request)
        {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|confirmed|'
            ], [
                'password.confirmed' => 'Mật khẩu xác nhận không khớp.'
            ]);
            $passwordReset = DB::table('password_resets')->where('email', $request->email)->first();

            if (!$passwordReset) {
                return response()->json(['message' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'], 400);
            }
            if (!Hash::check($request->token, $passwordReset->token)) {
                return response()->json(['message' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'], 400);
            }
            if (Carbon::parse($passwordReset->created_at)->addMinutes(60)->isPast()) {
                return response()->json(['message' => 'Liên kết đặt lại mật khẩu đã hết hạn.'], 400);
            }
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['message' => 'Không tìm thấy người dùng.'], 404);
            }
            $user->password = Hash::make($request->password);
            $user->save();

            DB::table('password_resets')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Mật khẩu của bạn đã được đặt lại thành công.'], 200);
        }
}
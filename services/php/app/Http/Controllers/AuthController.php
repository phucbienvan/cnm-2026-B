<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Auth\Events\Registered;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $input = $request->validated();
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password']
        ]);

        event(new Registered($user));

        return response()->json([
            'message' => 'register successfully, please check your email to verify your account',
            'data' => $user
        ]);
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        if (! $request->hasValidSignature()) {  
            return response()->json(['message' => 'Invalid or expired verification link'], 403);
        }

        $user = User::findOrFail($id);
        
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link'], 400);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }
        $user->markEmailAsVerified();
        return response()->json(['message' => 'Email verified successfully']);
    }

    public function login(LoginRequest $request)
    {
        $input = $request->validated();
        $user = User::where('email' , $input['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'email or password not match'
            ], 401);
        }

        if (!Hash::check($input['password'], $user->password)) {
            return response()->json([
                'message' => 'email or password not match'
            ], 401);
        }
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Your email address is not verified. Please verify your email before logging in.'
            ], 403);
        }

        $accessToken = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'login successfully',
            'data' => [
                'access_token' => $accessToken
            ]
        ]);
    }
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.exists' => 'Email does not exist in our system.'
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link has been sent to your email.'
            ], 200);
        }

        return response()->json([
            'message' => 'Unable to send password reset link.'
        ], 500);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => $password,
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Mật khẩu đã được cập nhật thành công. Vui lòng đăng nhập lại.'
            ], 200);
        }

        return response()->json([
            'message' => __($status) // Trả về thông báo lỗi cụ thể (ví dụ token không đúng/hết hạn)
        ], 400);
    }

    public function getUser()
    {
        $user = auth('sanctum')->user();
        return response()->json([
            'message' => 'get user successfully',
            'data' => $user
        ]);
    }
}

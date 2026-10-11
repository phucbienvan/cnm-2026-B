<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $input = $request->validated();
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']), 
            'verification_token' => Str::random(60),
            'email_verified_at' => null // Ban đầu chưa xác thực
        ]);

        // Tạo link xác thực gửi qua Mailtrap
        $verificationUrl = "http://localhost:8000/api/verify-email?token=" . $user->verification_token;

        // Gửi email qua Mailtrap
        Mail::raw("Chào {$user->name},\n\nVui lòng nhấn vào đường dẫn sau để xác thực tài khoản của bạn:\n{$verificationUrl}", function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Xác thực tài khoản đăng ký');
        });

        return response()->json([
            'message' => 'Register successfully. Please check your Mailtrap inbox to verify your email before logging in.',
            'data' => $user
        ], 201);
    }

    public function verifyEmail(Request $request)
    {
        $token = $request->query('token');
        $user = User::where('verification_token', $token)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid or expired verification token.'
            ], 400);
        }

        // Cập nhật trạng thái đã xác thực và xóa token
        $user->email_verified_at = now();
        $user->verification_token = null;
        $user->save();

        return response()->json([
            'message' => 'Email verified successfully. You can now login.'
        ]);
    }

    public function login(LoginRequest $request)
    {
        $input = $request->validated();
        $user = User::where('email', $input['email'])->first();

        if (!$user || !Hash::check($input['password'], $user->password)) {
            return response()->json([
                'message' => 'email or password not match'
            ], 401);
        }

        // Kiểm tra xem user đã xác thực email hay chưa
        if (is_null($user->email_verified_at)) {
            return response()->json([
                'message' => 'Your email has not been verified. Please check your Mailtrap inbox.'
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
        ]);

        $user = User::where('email', $request->email)->first();
        $resetToken = Str::random(60);
        
        // Lưu hoặc cập nhật token reset mật khẩu (tùy thuộc vào thiết kế bảng password_resets hoặc trực tiếp trên user)
        $user->verification_token = $resetToken; 
        $user->save();

        $resetUrl = "http://localhost:8000/api/reset-password?token=" . $resetToken;

        // Gửi mail hướng dẫn reset password qua Mailtrap
        Mail::raw("Bạn nhận được email này vì chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.\n\nLink đặt lại mật khẩu:\n{$resetUrl}", function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Khôi phục mật khẩu');
        });

        return response()->json([
            'message' => 'Password reset email sent successfully. Please check your Mailtrap.'
        ]);
    }

    public function getUser()
    {
        $user = auth('sanctum')->user();
        return response()->json([
            'message' => 'get user successfully',
            'data' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'logout successfully'
        ]);
    }
}
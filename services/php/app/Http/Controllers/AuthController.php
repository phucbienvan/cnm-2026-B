<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMailRegister;

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

        return response()->json([
            'message' => 'register successfully',
            'data' => $user
        ]);
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

        $accessToken = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'login successfully',
            'data' => [
                'access_token' => $accessToken
            ]
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
            'success' => true,
            'message' => 'Đăng xuất thành công.'
        ], 200);
    }

    public function resendCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.exists' => 'Email không tồn tại trong hệ thống.'
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        $user->code = (string) random_int(100000, 999999);
        $user->expired_code = now()->addMinutes(15);
        $user->save();

        Mail::to($user->email)->send(new SendMailRegister($user));

        return response()->json([
            'success' => true,
            'message' => 'Mã xác nhận mới đã được gửi đến email của bạn.'
        ], 200);
    }
}

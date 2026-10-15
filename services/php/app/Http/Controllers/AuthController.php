<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

    /**
     * Handle user logout and revoke API access token(s).
     * 
     * Hỗ trợ 2 chế độ đăng xuất:
     * 1. Mặc định: Chỉ thu hồi token của thiết bị hiện tại ($user->currentAccessToken()->delete()).
     * 2. Toàn bộ thiết bị: Khi truyền all_devices = true ($user->tokens()->delete()).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // Kiểm tra xem người dùng có yêu cầu đăng xuất khỏi tất cả thiết bị không
        if ($request->boolean('all_devices')) {
            // Xóa toàn bộ token của tài khoản này
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out from all devices successfully'
            ], 200);
        }

        // Mặc định: Chỉ xóa token hiện tại đang thực hiện request
        $currentToken = $user->currentAccessToken();
        if ($currentToken) {
            $currentToken->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout successfully'
        ], 200);
    }
}

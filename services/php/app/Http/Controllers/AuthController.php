<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendVerificationCodeRequest;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $input = $request->validated();
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        return response()->json([
            'message' => 'register successfully',
            'data' => $user,
        ]);
    }

    public function login(LoginRequest $request)
    {
        $input = $request->validated();
        $user = User::where('email', $input['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'email or password not match',
            ], 401);
        }

        if (! Hash::check($input['password'], $user->password)) {
            return response()->json([
                'message' => 'email or password not match',
            ], 401);
        }

        $accessToken = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'login successfully',
            'data' => [
                'access_token' => $accessToken,
            ],
        ]);
    }

    public function resendVerificationCode(ResendVerificationCodeRequest $request): JsonResponse
    {
        $email = $request->string('email')->lower()->toString();
        $user = User::where('email', $email)->first();

        if ($user && ! $user->email_verified_at) {
            $code = (string) random_int(100000, 999999);

            Cache::put(
                'email-verification-code:'.sha1($email),
                Hash::make($code),
                now()->addMinutes(10),
            );

            Mail::to($email)->send(new VerificationCodeMail($code));
        }

        return response()->json([
            'message' => 'verification code sent successfully',
        ]);
    }

    public function getUser()
    {
        $user = auth('sanctum')->user();

        return response()->json([
            'message' => 'get user successfully',
            'data' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'logout successfully',
        ]);
    }
}

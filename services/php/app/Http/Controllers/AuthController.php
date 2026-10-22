<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function resendCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower($validated['email']);
        $rateLimitKey = 'resend-email-code:'.$email;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            return response()->json([
                'message' => 'Please wait before requesting another code.',
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 3600);

        $user = User::where('email', $email)->first();

        if ($user && $user->email_verified_at === null) {
            $code = (string) random_int(100000, 999999);

            Cache::put('email-verification-code:'.$user->id, $code, now()->addMinutes(10));

            Mail::raw("Your email verification code is: {$code}. It expires in 10 minutes.", function ($message) use ($email): void {
                $message->to($email)->subject('Email verification code');
            });
        }

        return response()->json([
            'message' => 'If the email is registered and not verified, a verification code has been sent.',
        ]);
    }

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

    public function logout()
    {
        request()->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'logout successfully'
        ]);
    }
}

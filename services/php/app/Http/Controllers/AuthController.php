<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResendCodeMailRequest;
use App\Http\Requests\Auth\VerifyCodeRequest;
use App\Mail\SendMailRegister;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $input = $request->validated();

        $code = random_int(100000 , 999999);
        $expiredCode = now()->addMinutes(10);
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'code' => Hash::make($code),
            'expired_code' => $expiredCode
        ]);

        //Send mail
        Mail::to($user->email)->queue(new SendMailRegister($code,  $input['name']));

        return response()->json([
            'message' => 'register successfully',
            'data' => $user
        ]);
    }

    public function verifyCode(VerifyCodeRequest $request)
    {
        $input = $request->validated();
        $user = User::where('email', $input['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'user not found'
            ], 404);
        }

        if (!Hash::check($input['code'], $user->code)) {
            return response()->json([
                'message' => 'code is incorrect'
            ], 400);
        }

        if ($user->expired_code < now()) {
            return response()->json([
                'message' => 'code is expired'
            ], 400);
        }

        $user->update([
            'code' => null,
            'expired_code' => null,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'email verified successfully',
            'data' => $user
        ]);
    }

    public function resendCodeMail(ResendCodeMailRequest $request): JsonResponse
    {
        $email = mb_strtolower(trim($request->validated()['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        $successMessage = 'If the account exists and its email is not verified, a new code has been sent.';

        if (! $user || $user->email_verified_at) {
            return response()->json([
                'message' => $successMessage,
            ]);
        }

        $emailKey = hash('sha256', $email);
        $cooldownKey = "resend-code-mail:cooldown:{$emailKey}";
        $hourlyLimitKey = "resend-code-mail:hourly:{$emailKey}";

        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return response()->json([
                'message' => 'Please wait before requesting another verification code.',
                'retry_after' => RateLimiter::availableIn($cooldownKey),
            ], 429);
        }

        if (RateLimiter::tooManyAttempts($hourlyLimitKey, 5)) {
            return response()->json([
                'message' => 'The verification code resend limit has been reached. Please try again later.',
                'retry_after' => RateLimiter::availableIn($hourlyLimitKey),
            ], 429);
        }

        $code = (string) random_int(100000, 999999);
        $user->update([
            'code' => Hash::make($code),
            'expired_code' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->queue(new SendMailRegister($code, $user->name));

        RateLimiter::hit($cooldownKey, 60);
        RateLimiter::hit($hourlyLimitKey, 60 * 60);

        return response()->json([
            'message' => $successMessage,
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

        if (!$user->email_verified_at) {
            return response()->json([
                'message' => 'email not verified'
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

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'logout successfully'
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
}

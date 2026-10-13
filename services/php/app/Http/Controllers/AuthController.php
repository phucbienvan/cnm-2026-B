<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendVerificationCodeRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Mail\VerifyEmailCode;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $input = $request->validated();
        $code = (string) random_int(100000, 999999);
        $user = DB::transaction(function () use ($input, $code): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'code_verify_email' => Hash::make($code),
                'expired_verify_email' => now()->addMinutes(10),
            ]);

            Mail::to($user->email)->send(new VerifyEmailCode($code, $user->name));

            return $user;
        });

        return response()->json([
            'message' => 'register successfully; verification code sent',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $input = $request->validated();
        $attemptKey = 'verify-email:'.hash('sha256', strtolower($input['email']));

        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            return response()->json([
                'message' => 'too many verification attempts; try again later',
            ], 429);
        }

        $user = User::where('email', $input['email'])->firstOrFail();

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'email already verified',
            ], 409);
        }

        if (! $user->expired_verify_email || $user->expired_verify_email->isPast()) {
            RateLimiter::hit($attemptKey, 60);

            return response()->json([
                'message' => 'verification code expired',
            ], 422);
        }

        if (! $user->code_verify_email || ! Hash::check($input['code'], $user->code_verify_email)) {
            RateLimiter::hit($attemptKey, 60);

            return response()->json([
                'message' => 'verification code incorrect',
            ], 422);
        }

        $user->update([
            'email_verified_at' => now(),
            'code_verify_email' => null,
            'expired_verify_email' => null,
        ]);
        RateLimiter::clear($attemptKey);

        return response()->json([
            'message' => 'email verified successfully',
        ]);
    }

    public function resendVerificationCode(ResendVerificationCodeRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        $attemptKey = 'resend-verification:'.hash('sha256', strtolower($email));

        if (RateLimiter::tooManyAttempts($attemptKey, 1)) {
            return response()->json([
                'message' => 'If the email is registered and unverified, a verification code has been sent.',
            ], 202);
        }

        RateLimiter::hit($attemptKey, 60);

        $user = User::where('email', $email)
            ->whereNull('email_verified_at')
            ->first();

        if ($user) {
            $code = (string) random_int(100000, 999999);
            $user->update([
                'code_verify_email' => Hash::make($code),
                'expired_verify_email' => now()->addMinutes(10),
            ]);

            Mail::to($user->email)->send(new VerifyEmailCode($code, $user->name));
        }

        return response()->json([
            'message' => 'If the email is registered and unverified, a verification code has been sent.',
        ], 202);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->validated());

        return response()->json([
            'message' => 'If the email is registered, a password reset link has been sent.',
        ], 202);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset($request->validated(), function (User $user, string $password): void {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            $user->tokens()->delete();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'password reset token is invalid or expired',
            ], 422);
        }

        return response()->json([
            'message' => 'password reset successfully',
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $input = $request->validated();
        $attemptKey = 'login:'.hash('sha256', strtolower($input['email']));

        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            return response()->json([
                'message' => 'too many login attempts; try again later',
            ], 429);
        }

        $user = User::where('email', $input['email'])->first();

        if (! $user) {
            RateLimiter::hit($attemptKey, 60);

            return response()->json([
                'message' => 'email or password not match',
            ], 401);
        }

        if (! Hash::check($input['password'], $user->password)) {
            RateLimiter::hit($attemptKey, 60);

            return response()->json([
                'message' => 'email or password not match',
            ], 401);
        }

        if (! $user->email_verified_at) {
            return response()->json([
                'message' => 'email not verified',
            ], 403);
        }

        RateLimiter::clear($attemptKey);

        $accessToken = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'login successfully',
            'data' => [
                'access_token' => $accessToken,
            ],
        ]);
    }

    public function getUser(): JsonResponse
    {
        $user = auth('sanctum')->user();

        return response()->json([
            'message' => 'get user successfully',
            'data' => $user,
        ]);
    }
}

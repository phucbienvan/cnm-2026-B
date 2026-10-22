<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendVerificationCodeRequest;
use App\Mail\VerificationCodeMail;
use App\Models\User;
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
        $this->sendVerificationCode($user);

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

    public function logout()
    {
        auth('sanctum')->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'logout successfully',
        ]);
    }

    public function resendVerificationCode(ResendVerificationCodeRequest $request)
    {
        $user = User::where('email', $request->validated()['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'email not found',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'email is already verified',
            ], 422);
        }

        $this->sendVerificationCode($user);

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

    private function sendVerificationCode(User $user): void
    {
        $user->update([
            'email_verification_code' => (string) random_int(100000, 999999),
            'email_verification_code_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new VerificationCodeMail($user->email_verification_code));
    }
}

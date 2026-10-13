<?php

namespace Tests\Feature;

use App\Mail\PasswordResetLink;
use App\Mail\VerifyEmailCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_sends_a_verification_code_and_stores_only_its_hash(): void
    {
        Mail::fake();

        $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertOk()
            ->assertJsonPath('message', 'register successfully; verification code sent')
            ->assertJsonMissingPath('data.code_verify_email');

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->code_verify_email);
        $this->assertTrue($user->expired_verify_email->isFuture());

        Mail::assertSent(VerifyEmailCode::class, function (VerifyEmailCode $mail) use ($user): bool {
            return $mail->hasTo($user->email)
                && preg_match('/^\d{6}$/', $mail->code) === 1
                && Hash::check($mail->code, $user->code_verify_email);
        });
    }

    public function test_verification_code_verifies_email_and_is_consumed(): void
    {
        $user = User::factory()->unverified()->create([
            'code_verify_email' => Hash::make('123456'),
            'expired_verify_email' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/verify-email', [
            'email' => $user->email,
            'code' => '123456',
        ])->assertOk()
            ->assertJsonPath('message', 'email verified successfully');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->code_verify_email);
        $this->assertNull($user->expired_verify_email);
    }

    public function test_invalid_or_expired_verification_codes_are_rejected(): void
    {
        $user = User::factory()->unverified()->create([
            'code_verify_email' => Hash::make('123456'),
            'expired_verify_email' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/verify-email', [
            'email' => $user->email,
            'code' => '654321',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'verification code incorrect');

        $user->update(['expired_verify_email' => now()->subSecond()]);

        $this->postJson('/api/verify-email', [
            'email' => $user->email,
            'code' => '123456',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'verification code expired');
    }

    public function test_expired_verification_code_can_be_resent(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create([
            'code_verify_email' => Hash::make('123456'),
            'expired_verify_email' => now()->subMinute(),
        ]);

        $this->postJson('/api/resend-verification-code', [
            'email' => $user->email,
        ])->assertAccepted();

        $user->refresh();
        $this->assertNotSame('123456', $user->code_verify_email);
        $this->assertTrue($user->expired_verify_email->isFuture());
        Mail::assertSent(VerifyEmailCode::class, fn (VerifyEmailCode $mail): bool => $mail->hasTo($user->email));
    }

    public function test_resending_a_live_verification_code_invalidates_the_old_code(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create([
            'code_verify_email' => Hash::make('123456'),
            'expired_verify_email' => now()->addMinutes(10),
        ]);
        $resentCode = null;

        $this->postJson('/api/resend-verification-code', [
            'email' => $user->email,
        ])->assertAccepted();

        Mail::assertSent(VerifyEmailCode::class, function (VerifyEmailCode $mail) use ($user, &$resentCode): bool {
            $resentCode = $mail->code;

            return $mail->hasTo($user->email)
                && Hash::check($mail->code, $user->fresh()->code_verify_email);
        });
        $this->assertNotSame('123456', $resentCode);

        $this->postJson('/api/verify-email', [
            'email' => $user->email,
            'code' => '123456',
        ])->assertUnprocessable();

        $this->postJson('/api/verify-email', [
            'email' => $user->email,
            'code' => $resentCode,
        ])->assertOk();
    }

    public function test_verification_attempts_are_rate_limited_per_email_across_ip_addresses(): void
    {
        $user = User::factory()->unverified()->create([
            'code_verify_email' => Hash::make('123456'),
            'expired_verify_email' => now()->addMinutes(10),
        ]);
        $attemptKey = 'verify-email:'.hash('sha256', strtolower($user->email));
        RateLimiter::clear($attemptKey);

        foreach (range(1, 5) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.$attempt])
                ->postJson('/api/verify-email', [
                    'email' => $user->email,
                    'code' => '654321',
                ])->assertUnprocessable();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.6'])
            ->postJson('/api/verify-email', [
                'email' => $user->email,
                'code' => '654321',
            ])->assertTooManyRequests();
    }

    public function test_login_failures_are_rate_limited_per_email_across_ip_addresses(): void
    {
        $user = User::factory()->create();
        $attemptKey = 'login:'.hash('sha256', strtolower($user->email));
        RateLimiter::clear($attemptKey);

        foreach (range(1, 5) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.'.$attempt])
                ->postJson('/api/login', [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                ])->assertUnauthorized();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.6'])
            ->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertTooManyRequests();
    }

    public function test_unverified_user_cannot_log_in(): void
    {
        $user = User::factory()->unverified()->create([
            'code_verify_email' => Hash::make('123456'),
            'expired_verify_email' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertForbidden()
            ->assertJsonPath('message', 'email not verified');
    }

    public function test_existing_unverified_user_without_pending_code_cannot_log_in(): void
    {
        $user = User::factory()->unverified()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertForbidden()
            ->assertJsonPath('message', 'email not verified');
    }

    public function test_forgot_password_sends_a_reset_link_without_revealing_unknown_emails(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ])->assertAccepted()
            ->assertJsonPath('message', 'If the email is registered, a password reset link has been sent.');

        Mail::assertSent(PasswordResetLink::class, function (PasswordResetLink $mail) use ($user): bool {
            return str_contains($mail->resetUrl, rawurlencode($user->email))
                && str_contains($mail->resetUrl, 'token=');
        });

        $this->postJson('/api/forgot-password', [
            'email' => 'unknown@example.com',
        ])->assertAccepted()
            ->assertJsonPath('message', 'If the email is registered, a password reset link has been sent.');
    }

    public function test_reset_password_changes_password_and_revokes_existing_tokens(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);
        $accessToken = $user->createToken('authToken');

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk()
            ->assertJsonPath('message', 'password reset successfully');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $accessToken->accessToken->id,
        ]);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_reset_password_rejects_an_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'password reset token is invalid or expired');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_password_rejects_an_expired_token(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->travel(61)->minutes();

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'password reset token is invalid or expired');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}

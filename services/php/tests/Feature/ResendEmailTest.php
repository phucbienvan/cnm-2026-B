<?php

namespace Tests\Feature;

use App\Mail\SendMailRegister;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResendEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->freezeSecond();
    }

    public function test_it_resends_a_new_code_that_can_verify_the_email(): void
    {
        $user = User::factory()->unverified()->create([
            'code' => Hash::make('123456'),
            'expired_code' => now()->subMinute(),
        ]);
        $oldHash = $user->code;
        $sentCode = null;

        $this->postJson('/api/resend-email', ['email' => $user->email])
            ->assertOk()
            ->assertExactJson(['message' => 'verification email resent successfully']);

        $user->refresh();
        $this->assertNotSame($oldHash, $user->code);
        $this->assertTrue($user->expired_code->equalTo(now()->addMinutes(10)));
        $this->assertNull($user->email_verified_at);

        Mail::assertQueued(SendMailRegister::class, function (SendMailRegister $mail) use ($user, &$sentCode) {
            $sentCode = $mail->code;

            return $mail->hasTo($user->email)
                && $mail->username === $user->name
                && preg_match('/^[1-9][0-9]{5}$/', $mail->code) === 1
                && Hash::check($mail->code, $user->code);
        });
        Mail::assertQueuedCount(1);

        $this->postJson('/api/verify-code', ['email' => $user->email, 'code' => $sentCode])
            ->assertOk();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_it_rejects_missing_and_invalid_email_addresses(): void
    {
        $this->postJson('/api/resend-email')->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->travel(61)->seconds();
        $this->postJson('/api/resend-email', ['email' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');

        Mail::assertNothingQueued();
    }

    public function test_it_returns_not_found_for_an_unknown_email(): void
    {
        $this->postJson('/api/resend-email', ['email' => 'missing@example.com'])
            ->assertNotFound()->assertJsonPath('message', 'user not found');

        Mail::assertNothingQueued();
    }

    public function test_it_does_not_resend_for_an_already_verified_user(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/resend-email', ['email' => $user->email])
            ->assertStatus(400)->assertJsonPath('message', 'email already verified');

        $this->assertNull($user->fresh()->code);
        Mail::assertNothingQueued();
    }

    public function test_it_limits_resending_to_once_per_minute(): void
    {
        $user = User::factory()->unverified()->create();

        $this->postJson('/api/resend-email', ['email' => $user->email])->assertOk();
        $hash = $user->fresh()->code;

        $this->postJson('/api/resend-email', ['email' => $user->email])->assertTooManyRequests();
        $this->assertSame($hash, $user->fresh()->code);
        Mail::assertQueuedCount(1);

        $this->travel(61)->seconds();
        $this->postJson('/api/resend-email', ['email' => $user->email])->assertOk();
        Mail::assertQueuedCount(2);
    }
}

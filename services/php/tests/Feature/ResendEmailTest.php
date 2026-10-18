<?php

namespace Tests\Feature;

use App\Mail\SendMailRegister;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ResendEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('resend-email');
    }

    public function test_it_can_resend_verification_code_successfully(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
            'code' => Hash::make('111111'),
            'expired_code' => now()->subMinutes(5),
        ]);

        $response = $this->postJson('/api/resend-code', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'verification code resent successfully',
            ]);

        Mail::assertQueued(SendMailRegister::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });

        $user->refresh();
        $this->assertNotNull($user->code);
        $this->assertTrue($user->expired_code > now());
    }

    public function test_it_returns_404_when_user_does_not_exist(): void
    {
        $response = $this->postJson('/api/resend-code', [
            'email' => 'notfound@example.com',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'user not found',
            ]);
    }

    public function test_it_returns_400_when_email_already_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/resend-code', [
            'email' => 'verified@example.com',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'email is already verified',
            ]);
    }

    public function test_it_validates_email_input(): void
    {
        $response = $this->postJson('/api/resend-code', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_it_enforces_rate_limiting(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'ratelimit@example.com',
            'email_verified_at' => null,
        ]);

        // First request should succeed
        $firstResponse = $this->postJson('/api/resend-code', [
            'email' => 'ratelimit@example.com',
        ]);
        $firstResponse->assertStatus(200);

        // Second immediate request should be blocked by rate limiter
        $secondResponse = $this->postJson('/api/resend-code', [
            'email' => 'ratelimit@example.com',
        ]);
        $secondResponse->assertStatus(429);
    }
}

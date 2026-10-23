<?php

namespace Tests\Feature;

use App\Mail\SendMailRegister;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['access_token'],
            ]);

        $token = $loginResponse->json('data.access_token');

        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'message' => 'logout successfully',
            ]);

        // Attempting to access protected endpoint with the revoked token should fail
        $userResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/users');

        $userResponse->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    public function test_user_can_resend_verification_code(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
        ]);

        $response = $this->postJson('/api/resend-code', [
            'email' => 'unverified@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'verification code resent successfully',
            ]);

        Mail::assertQueued(SendMailRegister::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        $user->refresh();
        $this->assertNotNull($user->code);
        $this->assertNotNull($user->expired_code);
    }

    public function test_resend_code_fails_when_user_not_found(): void
    {
        $response = $this->postJson('/api/resend-code', [
            'email' => 'notfound@example.com',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'user not found',
            ]);
    }

    public function test_resend_code_fails_when_email_already_verified(): void
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
                'message' => 'email already verified',
            ]);
    }

    public function test_resend_code_is_rate_limited(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'ratelimit@example.com',
        ]);

        $this->postJson('/api/resend-code', [
            'email' => 'ratelimit@example.com',
        ])->assertStatus(200);

        $secondResponse = $this->postJson('/api/resend-code', [
            'email' => 'ratelimit@example.com',
        ]);

        $secondResponse->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);
    }
}

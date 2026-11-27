<?php

namespace Tests\Feature;

use App\Mail\SendMailRegister;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResendCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_resend_code_without_email(): void
    {
        $response = $this->postJson('/api/resend-code', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_cannot_resend_code_if_email_not_found(): void
    {
        $response = $this->postJson('/api/resend-code', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'user not found',
            ]);
    }

    public function test_user_cannot_resend_code_if_email_already_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/resend-code', [
            'email' => $user->email,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'email already verified',
            ]);
    }

    public function test_user_can_resend_code_successfully(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
            'code' => Hash::make('111111'),
            'expired_code' => now()->subMinutes(5),
        ]);

        $response = $this->postJson('/api/resend-code', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'resend code successfully',
            ]);

        Mail::assertQueued(SendMailRegister::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        $user->refresh();
        $this->assertNotNull($user->code);
        $this->assertNotNull($user->expired_code);
        $this->assertTrue($user->expired_code > now());
    }
}

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

    public function test_resend_code_successfully(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Nguyen Thi Thuong',
            'email' => 'thuong@example.com',
            'password' => Hash::make('password123'),
            'code' => Hash::make('123456'),
            'expired_code' => now()->addMinutes(5),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/resend-code', [
            'email' => 'thuong@example.com',
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
    }

    public function test_resend_code_returns_404_when_user_not_found(): void
    {
        $response = $this->postJson('/api/resend-code', [
            'email' => 'notfound@example.com',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'user not found',
            ]);
    }

    public function test_resend_code_returns_400_when_email_already_verified(): void
    {
        User::create([
            'name' => 'Nguyen Thi Thuong',
            'email' => 'verified@example.com',
            'password' => Hash::make('password123'),
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

    public function test_resend_code_validation_error(): void
    {
        $response = $this->postJson('/api/resend-code', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}

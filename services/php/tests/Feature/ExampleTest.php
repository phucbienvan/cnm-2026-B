<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withToken($token->plainTextToken)->postJson('/api/logout');

        $response->assertOk()->assertJson([
            'message' => 'logout successfully',
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
        $this->app['auth']->forgetGuards();
        $this->withToken($token->plainTextToken)
            ->getJson('/api/users')
            ->assertUnauthorized();
    }

    public function test_guest_cannot_logout(): void
    {
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_user_can_resend_email_verification_code(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->postJson('/api/resend-verification-code', [
            'email' => $user->email,
        ]);

        $response->assertOk()->assertJson([
            'message' => 'verification code sent successfully',
        ]);
        $user->refresh();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $user->email_verification_code);
        $this->assertTrue($user->email_verification_code_expires_at->isFuture());
        Mail::assertSent(VerificationCodeMail::class, function (VerificationCodeMail $mail) use ($user) {
            return $mail->code === $user->email_verification_code;
        });
    }

    public function test_verified_user_cannot_resend_email_verification_code(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->postJson('/api/resend-verification-code', [
            'email' => $user->email,
        ])->assertStatus(422);

        Mail::assertNothingSent();
    }
}

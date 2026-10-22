<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResendVerificationCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_user_can_receive_a_new_verification_code(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();

        $response = $this->postJson('/api/resend-verification-code', [
            'email' => $user->email,
        ]);

        $response->assertOk()->assertJson([
            'message' => 'verification code sent successfully',
        ]);

        Mail::assertSent(VerificationCodeMail::class, function (VerificationCodeMail $mail) use ($user): bool {
            return $mail->hasTo($user->email) && preg_match('/^\\d{6}$/', $mail->code) === 1;
        });

        $storedCode = Cache::get('email-verification-code:'.sha1($user->email));

        $this->assertIsString($storedCode);
    }

    public function test_unknown_email_receives_the_same_response_without_sending_mail(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/resend-verification-code', [
            'email' => 'unknown@example.com',
        ]);

        $response->assertOk()->assertJson([
            'message' => 'verification code sent successfully',
        ]);

        Mail::assertNothingSent();
    }

    public function test_email_is_required(): void
    {
        $this->postJson('/api/resend-verification-code')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}

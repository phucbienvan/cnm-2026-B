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

    public function test_unverified_user_can_resend_and_verify_the_new_code(): void
    {
        Mail::fake();
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->unverified()->create([
            'code' => Hash::make('123456'),
            'expired_code' => now()->subMinute(),
        ]);
        $oldHash = $user->code;

        $this->postJson('/api/resend-code', ['email' => $user->email])
            ->assertOk()
            ->assertJson(['message' => 'resend code successfully']);

        $user->refresh();
        $this->assertNotSame($oldHash, $user->code);
        $this->assertTrue($user->expired_code->equalTo(now()->addMinutes(10)));
        $this->assertNull($user->email_verified_at);

        Mail::assertQueued(SendMailRegister::class, function (SendMailRegister $mail) use ($user) {
            $this->assertTrue($mail->hasTo($user->email));
            $this->assertSame($user->name, $mail->username);
            $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $mail->code);
            $this->assertTrue(Hash::check($mail->code, $user->code));

            $this->postJson('/api/verify-code', [
                'email' => $user->email,
                'code' => $mail->code,
            ])->assertOk();

            return true;
        });
        Mail::assertQueuedCount(1);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_unknown_email_cannot_resend_code(): void
    {
        Mail::fake();

        $this->postJson('/api/resend-code', ['email' => 'missing@example.com'])
            ->assertNotFound()
            ->assertJson(['message' => 'user not found']);

        Mail::assertNothingQueued();
    }

    public function test_verified_user_cannot_resend_code(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->postJson('/api/resend-code', ['email' => $user->email])
            ->assertStatus(400)
            ->assertJson(['message' => 'email already verified']);

        $this->assertNull($user->fresh()->code);
        Mail::assertNothingQueued();
    }

    public function test_email_is_required_and_must_be_valid(): void
    {
        Mail::fake();

        foreach ([[], ['email' => 'invalid-email']] as $input) {
            $this->postJson('/api/resend-code', $input)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('email');
        }

        Mail::assertNothingQueued();
    }
}

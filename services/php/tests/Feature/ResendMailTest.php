<?php

namespace Tests\Feature;

use App\Mail\SendMailRegister;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ResendMailTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_resends_an_expired_code_and_allows_verification_with_the_new_code(): void
    {
        Mail::fake();
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(12, 0));
        $user = User::factory()->unverified()->create([
            'code' => Hash::make('123456'),
            'expired_code' => now()->subMinute(),
        ]);

        $this->postJson('/api/resend-mail', ['email' => $user->email])
            ->assertOk()
            ->assertExactJson(['message' => 'verification email resent successfully']);

        Mail::assertQueued(SendMailRegister::class, function (SendMailRegister $mail) use ($user): bool {
            return $mail->hasTo($user->email) && $mail->username === $user->name;
        });
        Mail::assertQueuedCount(1);
        $mail = Mail::queued(SendMailRegister::class)->sole();
        $user->refresh();

        $this->assertMatchesRegularExpression('/^[1-9][0-9]{5}$/', $mail->code);
        $this->assertTrue(Hash::check($mail->code, $user->code));
        $this->assertSame('2026-10-23 12:10:00', $user->expired_code->toDateTimeString());
        $this->assertNull($user->email_verified_at);

        $this->postJson('/api/verify-code', ['email' => $user->email, 'code' => '123456'])
            ->assertBadRequest()
            ->assertJsonPath('message', 'code is incorrect');

        $this->postJson('/api/verify-code', ['email' => $user->email, 'code' => $mail->code])
            ->assertOk()
            ->assertJsonPath('message', 'email verified successfully');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email_verified_at' => '2026-10-23 12:00:00',
            'code' => null,
            'expired_code' => null,
        ]);
    }

    public function test_returns_400_without_sending_mail_or_changing_a_verified_user(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $attributes = $user->refresh()->getAttributes();

        $this->postJson('/api/resend-mail', ['email' => $user->email])
            ->assertBadRequest()
            ->assertExactJson(['message' => 'email already verified']);

        Mail::assertNothingOutgoing();
        $this->assertSame($attributes, $user->fresh()->getAttributes());
    }

    public function test_returns_404_without_sending_mail_for_an_unknown_email(): void
    {
        Mail::fake();

        $this->postJson('/api/resend-mail', ['email' => 'missing@example.com'])
            ->assertNotFound()
            ->assertExactJson(['message' => 'user not found']);

        Mail::assertNothingOutgoing();
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[TestWith([[], 'The email field is required.'])]
    #[TestWith([['email' => 'invalid'], 'The email field must be a valid email address.'])]
    #[TestWith([['email' => ['user@example.com']], 'The email field must be a string.'])]
    public function test_returns_422_without_sending_mail_for_invalid_input(array $payload, string $message): void
    {
        Mail::fake();

        $this->postJson('/api/resend-mail', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => $message]);

        Mail::assertNothingOutgoing();
    }

    public function test_returns_422_when_the_email_exceeds_255_characters(): void
    {
        Mail::fake();
        $email = str_repeat('a', 64).'@'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 61).'.com';

        $this->postJson('/api/resend-mail', ['email' => $email])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'The email field must not be greater than 255 characters.']);

        Mail::assertNothingOutgoing();
    }

    public function test_returns_429_for_repeat_requests_and_allows_resending_after_one_minute(): void
    {
        Mail::fake();
        $this->freezeTime();
        $user = User::factory()->unverified()->create();

        $this->postJson('/api/resend-mail', ['email' => $user->email])->assertOk();
        $attributes = $user->fresh()->getAttributes();

        $this->postJson('/api/resend-mail', ['email' => $user->email])
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');

        Mail::assertQueuedCount(1);
        $this->assertSame($attributes, $user->fresh()->getAttributes());

        $this->travel(61)->seconds();

        $this->postJson('/api/resend-mail', ['email' => $user->email])->assertOk();

        Mail::assertQueuedCount(2);
    }
}

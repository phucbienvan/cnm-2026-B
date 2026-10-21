<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Mail\PasswordResetLink;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'email_verified_at', 'code_verify_email', 'expired_verify_email'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'expired_verify_email' => 'datetime',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = rtrim((string) config('services.frontend_url'), '/')
            .'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $this->getEmailForPasswordReset(),
            ]);

        Mail::to($this->email)->send(new PasswordResetLink($resetUrl));
    }
}

<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('resend-email', function (Request $request) {
            $email = (string) $request->input('email', $request->ip());

            return Limit::perMinute(1)->by($email)->response(function () {
                return response()->json([
                    'message' => 'Too many requests. Please wait before requesting another code.',
                ], 429);
            });
        });
    }
}

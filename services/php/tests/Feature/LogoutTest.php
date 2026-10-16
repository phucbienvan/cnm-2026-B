<?php

namespace Tests\Feature;

use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_logout_ends_authentication_and_invalidates_the_session(): void
    {
        $user = UserFactory::new()->create();
        $guard = Auth::guard('web');
        $this->withSession([
            $guard->getName() => $user->getAuthIdentifier(),
            'checkout_id' => 'private-checkout',
        ]);
        $session = $this->app['session.store'];
        $previousSessionId = $session->getId();
        $previousCsrfToken = $session->token();
        $previousRememberToken = $user->getRememberToken();

        $response = $this->postJson(route('logout'));

        $response->assertOk()
            ->assertExactJson(['message' => 'logout successfully'])
            ->assertSessionMissing([$guard->getName(), 'checkout_id']);
        $this->assertGuest('web');
        $this->assertNotSame($previousSessionId, $session->getId());
        $this->assertNotEmpty($session->token());
        $this->assertNotSame($previousCsrfToken, $session->token());
        $this->assertNotSame($previousRememberToken, $user->fresh()->getRememberToken());
    }

    public function test_logout_succeeds_when_already_logged_out(): void
    {
        $response = $this->postJson(route('logout'));

        $response->assertOk()->assertExactJson(['message' => 'logout successfully']);
        $this->assertGuest('web');
    }

    public function test_get_logout_returns_405_without_logging_out_the_user(): void
    {
        $user = UserFactory::new()->create();
        $this->actingAs($user, 'web');

        $response = $this->getJson(route('logout'));

        $response->assertMethodNotAllowed();
        $this->assertAuthenticatedAs($user, 'web');
    }
}

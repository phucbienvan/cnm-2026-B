<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout_and_revoke_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('authToken')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $response->assertOk()
            ->assertJson([
                'message' => 'logout successfully',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Subsequent authenticated request using the revoked token must fail with 401
        $this->app['auth']->forgetGuards();
        $subsequentResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users');

        $subsequentResponse->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertUnauthorized();
    }

    public function test_logout_only_revokes_current_token(): void
    {
        $user = User::factory()->create();

        $token1 = $user->createToken('device_1')->plainTextToken;
        $token2 = $user->createToken('device_2')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 2);

        // Logout using token1
        $response = $this->withHeader('Authorization', 'Bearer '.$token1)
            ->postJson('/api/logout');

        $response->assertOk()
            ->assertJson([
                'message' => 'logout successfully',
            ]);

        // token1 should be deleted, token2 should remain
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // token1 is revoked
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token1)
            ->getJson('/api/users')
            ->assertUnauthorized();

        // token2 remains valid
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token2)
            ->getJson('/api/users')
            ->assertOk()
            ->assertJson([
                'message' => 'get user successfully',
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }
}

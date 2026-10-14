<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout_successfully(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('authToken')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $response->assertOk()
            ->assertJson([
                'message' => 'logout successfully',
            ]);

        $this->assertCount(0, $user->tokens()->get());

        // Reset auth state in test application container to simulate a new HTTP request
        $this->app['auth']->forgetGuards();

        $subsequentResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $subsequentResponse->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertUnauthorized();
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'thu@example.com',
            'password' => bcrypt('password123'),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'thu@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => ['access_token'],
            ]);

        $token = $loginResponse->json('data.access_token');

        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $logoutResponse->assertOk()
            ->assertJson([
                'message' => 'logout successfully',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }
}

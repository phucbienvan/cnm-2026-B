<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['access_token'],
            ]);

        $token = $loginResponse->json('data.access_token');

        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'message' => 'logout successfully',
            ]);

        // Attempting to access protected endpoint with the revoked token should fail
        $userResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/users');

        $userResponse->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }
}

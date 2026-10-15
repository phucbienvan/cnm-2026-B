<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_logout_successfully(): void
    {
        // Arrange: tạo user và đăng nhập lấy token
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.access_token');

        // Xác nhận token tồn tại trong DB trước khi logout
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // Act: gọi API logout với token
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/logout');

        // Assert: response đúng
        $response->assertStatus(200)
                 ->assertJson(['message' => 'logout successfully']);

        // Token phải bị xóa khỏi database
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_requires_authentication(): void
    {
        // Gọi logout không có token → 401
        $this->postJson('/api/logout')
             ->assertStatus(401);
    }

    public function test_logout_using_sanctum_helper(): void
    {
        // Dùng Sanctum::actingAs để tạo token tự động
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/logout')
             ->assertStatus(200)
             ->assertJson(['message' => 'logout successfully']);
    }
}

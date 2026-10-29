<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_delete_their_own_product(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Owned product',
            'price' => 100,
            'description' => 'Product owned by the authenticated user',
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertOk()
            ->assertJson(['message' => 'delete product successfully']);
        $this->assertSoftDeleted($product);
    }

    public function test_a_user_cannot_delete_another_users_product(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = Product::create([
            'name' => 'Protected product',
            'price' => 100,
            'description' => 'Product owned by another user',
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertUnauthorized()
            ->assertJson(['message' => 'you are not authorized to delete this product']);
        $this->assertNotSoftDeleted($product);
    }
}

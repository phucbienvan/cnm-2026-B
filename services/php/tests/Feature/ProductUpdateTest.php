<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_product_completely(): void
    {
        $product = Product::factory()->create([
            'name' => 'Old Name',
            'price' => 100,
            'description' => 'Old Description',
        ]);

        $updateData = [
            'name' => 'New Name',
            'price' => 250,
            'description' => 'New Description',
        ];

        $response = $this->putJson("/api/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'update product successfully',
                'data' => [
                    'name' => 'New Name',
                    'price' => 250,
                    'description' => 'New Description',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Name',
            'price' => 250,
            'description' => 'New Description',
        ]);
    }

    public function test_can_update_product_partially(): void
    {
        $product = Product::factory()->create([
            'name' => 'Original Name',
            'price' => 500,
            'description' => 'Original Description',
        ]);

        $response = $this->patchJson("/api/products/{$product->id}", [
            'price' => 750,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'update product successfully',
                'data' => [
                    'name' => 'Original Name',
                    'price' => 750,
                    'description' => 'Original Description',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Original Name',
            'price' => 750,
            'description' => 'Original Description',
        ]);
    }

    public function test_cannot_update_product_with_invalid_data(): void
    {
        $product = Product::factory()->create([
            'name' => 'Product Name',
            'price' => 100,
            'description' => 'Product Description',
        ]);

        $response = $this->putJson("/api/products/{$product->id}", [
            'price' => 'invalid-price',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_returns_404_when_product_not_found(): void
    {
        $response = $this->putJson('/api/products/999999', [
            'name' => 'Updated Name',
            'price' => 200,
            'description' => 'Updated Description',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'product not found',
            ]);
    }
}

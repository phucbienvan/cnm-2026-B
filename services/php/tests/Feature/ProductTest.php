<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_product_successfully(): void
    {
        $product = Product::create([
            'name' => 'Original Name',
            'price' => 100,
            'description' => 'Original description',
        ]);

        $payload = [
            'name' => 'Updated Product Name',
            'price' => 250,
            'description' => 'Updated description content',
        ];

        $response = $this->putJson("/api/products/{$product->id}", $payload);

        $response->assertOk()
            ->assertJson([
                'message' => 'update product successfully',
                'data' => [
                    'name' => 'Updated Product Name',
                    'price' => 250,
                    'description' => 'Updated description content',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'price' => 250,
        ]);
    }

    public function test_can_partially_update_product_with_patch(): void
    {
        $product = Product::create([
            'name' => 'Original Name',
            'price' => 100,
            'description' => 'Original description',
        ]);

        $response = $this->patchJson("/api/products/{$product->id}", [
            'price' => 199,
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'update product successfully',
                'data' => [
                    'name' => 'Original Name',
                    'price' => 199,
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => 199,
            'name' => 'Original Name',
        ]);
    }

    public function test_update_product_validation_fails_for_invalid_price(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 50,
            'description' => 'Description',
        ]);

        $response = $this->putJson("/api/products/{$product->id}", [
            'price' => 'not-a-number',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    public function test_update_returns_404_when_product_not_found(): void
    {
        $response = $this->putJson('/api/products/999999', [
            'name' => 'Non existent product',
            'price' => 100,
            'description' => 'Not found desc',
        ]);

        $response->assertNotFound()
            ->assertJson([
                'message' => 'product not found',
            ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_list_of_products(): void
    {
        Product::factory()->create([
            'name' => 'Laptop Dell',
            'price' => 15000000,
            'description' => 'Dell XPS 13'
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'get list product successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price', 'description', 'created_at', 'updated_at']
                ]
            ]);
    }

    public function test_can_create_product(): void
    {
        $payload = [
            'name' => 'iPhone 15 Pro',
            'price' => 25000000,
            'description' => 'Titanium Natural 256GB'
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'create product successfully',
                'data' => [
                    'name' => 'iPhone 15 Pro',
                    'price' => 25000000,
                    'description' => 'Titanium Natural 256GB'
                ]
            ]);

        $this->assertDatabaseHas('products', $payload);
    }

    public function test_can_show_product(): void
    {
        $product = Product::create([
            'name' => 'Mouse Logitech',
            'price' => 500000,
            'description' => 'Wireless MX Master 3S'
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'get product successfully',
                'data' => [
                    'id' => $product->id,
                    'name' => 'Mouse Logitech'
                ]
            ]);
    }

    public function test_can_update_product(): void
    {
        $product = Product::create([
            'name' => 'Old Product Name',
            'price' => 100000,
            'description' => 'Old Description'
        ]);

        $payload = [
            'name' => 'New Product Name',
            'price' => 200000,
            'description' => 'New Description'
        ];

        $response = $this->putJson("/api/products/{$product->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'update product successfully',
                'data' => [
                    'id' => $product->id,
                    'name' => 'New Product Name',
                    'price' => 200000,
                    'description' => 'New Description'
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Product Name'
        ]);
    }

    public function test_can_delete_product(): void
    {
        $product = Product::create([
            'name' => 'Product To Delete',
            'price' => 10000,
            'description' => 'Will be soft deleted'
        ]);

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'delete product successfully'
            ]);

        $this->assertSoftDeleted('products', [
            'id' => $product->id
        ]);
    }
}

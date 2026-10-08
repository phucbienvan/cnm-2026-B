<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductUpdateTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $app['config']->set(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);

        return $app;
    }

    public function test_put_updates_product_and_returns_saved_data(): void
    {
        $product = $this->createProduct();
        $input = ['name' => 'Updated product', 'price' => 200, 'description' => 'Updated description'];

        $response = $this->putJson('/api/products/'.$product->id, $input);

        $response->assertOk()
            ->assertJsonPath('message', 'update product successfully')
            ->assertJsonPath('data.name', 'Updated product')
            ->assertJsonPath('data.price', 200)
            ->assertJsonPath('data.description', 'Updated description');
        $this->assertDatabaseHas('products', ['id' => $product->id, ...$input]);
    }

    public function test_patch_updates_one_field_and_preserves_other_fields(): void
    {
        $product = $this->createProduct();

        $response = $this->patchJson('/api/products/'.$product->id, ['price' => 0]);

        $response->assertOk()->assertJsonPath('data.price', 0);
        $this->assertDatabaseHas('products', [
            'id' => $product->id, 'name' => 'Original product', 'price' => 0, 'description' => 'Original description',
        ]);
    }

    public function test_put_returns_422_for_missing_fields_without_changing_product(): void
    {
        $product = $this->createProduct();

        $response = $this->putJson('/api/products/'.$product->id, []);

        $response->assertUnprocessable()->assertJsonValidationErrors(['name', 'price', 'description']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Original product', 'price' => 100, 'description' => 'Original description']);
    }

    #[DataProvider('invalidUpdates')]
    public function test_patch_returns_422_for_invalid_fields_without_changing_product(array $input, string $field): void
    {
        $product = $this->createProduct();

        $response = $this->patchJson('/api/products/'.$product->id, $input);

        $response->assertUnprocessable()->assertJsonValidationErrors([$field]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Original product', 'price' => 100, 'description' => 'Original description']);
    }

    public static function invalidUpdates(): array
    {
        return [
            'empty name' => [['name' => ''], 'name'],
            'non-string name' => [['name' => 123], 'name'],
            'long name' => [['name' => str_repeat('a', 256)], 'name'],
            'non-numeric price' => [['price' => 'invalid'], 'price'],
            'fractional price' => [['price' => 1.5], 'price'],
            'negative price' => [['price' => -1], 'price'],
            'overflowing price' => [['price' => 2147483648], 'price'],
            'null price' => [['price' => null], 'price'],
            'empty description' => [['description' => ''], 'description'],
            'non-string description' => [['description' => []], 'description'],
        ];
    }

    public function test_update_ignores_unvalidated_fields(): void
    {
        $product = $this->createProduct();

        $response = $this->patchJson('/api/products/'.$product->id, [
            'name' => 'Updated product', 'id' => 999, 'deleted_at' => '2026-01-01 00:00:00',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated product', 'deleted_at' => null]);
        $this->assertDatabaseMissing('products', ['id' => 999]);
    }

    public function test_update_returns_404_for_missing_product(): void
    {
        $response = $this->putJson('/api/products/999', [
            'name' => 'Updated product', 'price' => 200, 'description' => 'Updated description',
        ]);

        $response->assertNotFound()->assertExactJson(['message' => 'product not found']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_update_returns_404_for_soft_deleted_product(): void
    {
        $product = $this->createProduct();
        $product->delete();

        $response = $this->patchJson('/api/products/'.$product->id, ['name' => 'Updated product']);

        $response->assertNotFound()->assertExactJson(['message' => 'product not found']);
        $this->assertSoftDeleted($product);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Original product']);
    }

    private function createProduct(): Product
    {
        return Product::create(['name' => 'Original product', 'price' => 100, 'description' => 'Original description']);
    }
}

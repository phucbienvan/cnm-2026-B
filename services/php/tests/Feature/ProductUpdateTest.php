<?php

namespace Tests\Feature;

use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProductUpdateTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_put_updates_the_product_and_returns_its_resource(): void
    {
        $product = ProductFactory::new()->create();
        $otherProduct = ProductFactory::new()->create();
        $otherAttributes = $otherProduct->fresh()->getAttributes();
        $input = [
            'name' => 'Updated product',
            'price' => 250000,
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/products/{$product->id}", $input);

        $response->assertOk()
            ->assertJsonPath('message', 'update product successfully')
            ->assertJsonPath('data.name', 'Updated product')
            ->assertJsonPath('data.price', 250000)
            ->assertJsonPath('data.description', 'Updated description')
            ->assertJsonStructure(['data' => ['created_at', 'updated_at']]);
        $this->assertDatabaseHas('products', ['id' => $product->id, ...$input]);
        $this->assertDatabaseCount('products', 2);
        $this->assertSame($otherAttributes, $otherProduct->fresh()->getAttributes());
    }

    #[TestWith(['name', 'Renamed product'])]
    #[TestWith(['price', 0])]
    #[TestWith(['description', 'Revised description'])]
    public function test_patch_preserves_fields_that_are_not_provided(string $field, string|int $value): void
    {
        $original = [
            'name' => 'Original product',
            'price' => 100000,
            'description' => 'Original description',
        ];
        $product = ProductFactory::new()->create($original);
        $expected = array_replace($original, [$field => $value]);

        $response = $this->patchJson("/api/products/{$product->id}", [$field => $value]);

        $response->assertOk()->assertJson(['data' => $expected]);
        $this->assertDatabaseHas('products', ['id' => $product->id, ...$expected]);
    }

    public function test_put_returns_422_when_required_fields_are_missing(): void
    {
        $product = ProductFactory::new()->create();
        $original = $product->fresh()->getAttributes();

        $response = $this->putJson("/api/products/{$product->id}", []);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'name' => 'The name field is required.',
            'price' => 'The price field is required.',
            'description' => 'The description field is required.',
        ]);
        $this->assertSame($original, $product->fresh()->getAttributes());
    }

    #[DataProvider('invalidUpdates')]
    public function test_invalid_updates_return_422_without_changing_the_product(
        string $method,
        string $field,
        mixed $value,
        string $message,
    ): void {
        $product = ProductFactory::new()->create();
        $original = $product->fresh()->getAttributes();
        $input = array_replace($product->only(['name', 'price', 'description']), [$field => $value]);

        $response = $this->json($method, "/api/products/{$product->id}", $input);

        $response->assertUnprocessable()->assertJsonValidationErrors([$field => $message]);
        $this->assertSame($original, $product->fresh()->getAttributes());
    }

    /**
     * @return array<string, array{string, string, mixed, string}>
     */
    public static function invalidUpdates(): array
    {
        return [
            'put invalid price' => ['PUT', 'price', 'invalid', 'The price field must be a number.'],
            'patch non-string name' => ['PATCH', 'name', 123, 'The name field must be a string.'],
            'patch long name' => ['PATCH', 'name', str_repeat('a', 256), 'The name field must not be greater than 255 characters.'],
            'patch empty name' => ['PATCH', 'name', '', 'The name field is required.'],
            'patch null price' => ['PATCH', 'price', null, 'The price field is required.'],
            'patch invalid price' => ['PATCH', 'price', 'invalid', 'The price field must be a number.'],
            'patch non-string description' => ['PATCH', 'description', [], 'The description field is required.'],
            'patch invalid description type' => ['PATCH', 'description', 123, 'The description field must be a string.'],
            'patch null description' => ['PATCH', 'description', null, 'The description field is required.'],
        ];
    }

    #[TestWith(['PUT'])]
    #[TestWith(['PATCH'])]
    public function test_updates_return_404_for_a_missing_product(string $method): void
    {
        $input = ProductFactory::new()->raw();

        $response = $this->json($method, '/api/products/999', $input);

        $response->assertNotFound()->assertExactJson(['message' => 'product not found']);
        $this->assertDatabaseCount('products', 0);
    }

    #[TestWith(['PUT'])]
    #[TestWith(['PATCH'])]
    public function test_updates_return_404_for_a_soft_deleted_product(string $method): void
    {
        $product = ProductFactory::new()->create();
        $product->delete();
        $original = $product->fresh()->getAttributes();

        $response = $this->json($method, "/api/products/{$product->id}", [
            'name' => 'Updated product',
            'price' => 250000,
            'description' => 'Updated description',
        ]);

        $response->assertNotFound()->assertExactJson(['message' => 'product not found']);
        $this->assertSoftDeleted($product);
        $this->assertSame($original, $product->fresh()->getAttributes());
    }

    public function test_update_ignores_attributes_outside_the_validated_fields(): void
    {
        $product = ProductFactory::new()->create();
        $createdAt = $product->getRawOriginal('created_at');

        $response = $this->patchJson("/api/products/{$product->id}", [
            'name' => 'Updated product',
            'id' => 999,
            'created_at' => '2000-01-01 00:00:00',
            'deleted_at' => '2000-01-01 00:00:00',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Updated product');
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated product',
            'created_at' => $createdAt,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseMissing('products', ['id' => 999]);
    }
}

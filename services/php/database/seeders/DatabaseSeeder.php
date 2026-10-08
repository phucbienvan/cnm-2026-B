<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Product::create([
            'name' => 'iPhone 15 Pro Max',
            'price' => 28000000,
            'description' => 'Điện thoại Apple iPhone 15 Pro Max 256GB'
        ]);

        Product::create([
            'name' => 'Laptop Dell XPS 13',
            'price' => 32000000,
            'description' => 'Laptop Dell XPS 13 Intel Core i7 16GB RAM'
        ]);

        Product::create([
            'name' => 'Tai nghe Sony WH-1000XM5',
            'price' => 8490000,
            'description' => 'Tai nghe chống ồn cao cấp Sony WH-1000XM5'
        ]);

        Product::create([
            'name' => 'Bàn phím cơ Keychron K2',
            'price' => 1950000,
            'description' => 'Bàn phím cơ không dây Keychron K2 V2'
        ]);

        Product::create([
            'name' => 'Chuột Logitech MX Master 3S',
            'price' => 2490000,
            'description' => 'Chuột không dây công thái học Logitech'
        ]);
    }
}

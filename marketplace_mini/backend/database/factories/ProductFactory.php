<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productNames = [
            'Wireless Bluetooth Headphones',
            'Smart Watch Pro',
            'Laptop Stand Adjustable',
            'USB-C Hub Adapter',
            'Mechanical Keyboard RGB',
            'Cotton T-Shirt Classic',
            'Denim Jeans Slim Fit',
            'Running Shoes Comfortable',
            'Backpack Travel Waterproof',
            'Sunglasses UV Protection',
            'Coffee Maker Automatic',
            'Blender High Speed',
            'Air Purifier HEPA Filter',
            'Desk Lamp LED Adjustable',
            'Storage Organizer Box',
            'Fiction Novel Bestseller',
            'Cookbook Healthy Recipes',
            'Self-Help Guide Success',
            'Mystery Thriller Book',
            'Science Fiction Novel',
            'Building Blocks Set',
            'Remote Control Car',
            'Educational Puzzle Game',
            'Stuffed Animal Bear',
            'Board Game Family Fun',
        ];

        $name = fake()->randomElement($productNames) . ' ' . fake()->numberBetween(1, 99);

        return [
            'seller_id' => User::where('role', 'seller')->inRandomOrder()->first()?->id ?? User::factory()->seller(),
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->paragraphs(3, true),
            'price' => fake()->randomFloat(2, 9.99, 999.99),
            'stock_quantity' => fake()->numberBetween(0, 500),
            'image_url' => fake()->imageUrl(640, 480, 'products', true),
        ];
    }
}

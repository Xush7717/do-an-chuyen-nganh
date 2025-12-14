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

        // High-quality category-specific images from Unsplash
        $categoryImages = [
            'electronics' => [
                'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=640&h=480&fit=crop', // Headphones
                'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=640&h=480&fit=crop', // Watch
                'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=640&h=480&fit=crop', // Keyboard
                'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=640&h=480&fit=crop', // Laptop
                'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=640&h=480&fit=crop', // Smart device
                'https://images.unsplash.com/photo-1585060544812-6b45742d762f?w=640&h=480&fit=crop', // Speaker
                'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=640&h=480&fit=crop', // Camera
                'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=640&h=480&fit=crop', // Monitor
            ],
            'fashion' => [
                'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=640&h=480&fit=crop', // T-shirt
                'https://images.unsplash.com/photo-1542272604-787c3835535d?w=640&h=480&fit=crop', // Jeans
                'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=640&h=480&fit=crop', // Shoes
                'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=640&h=480&fit=crop', // Backpack
                'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=640&h=480&fit=crop', // Sunglasses
                'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=640&h=480&fit=crop', // Dress
                'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=640&h=480&fit=crop', // Jacket
                'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=640&h=480&fit=crop', // Watch
            ],
            'home-living' => [
                'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=640&h=480&fit=crop', // Coffee maker
                'https://images.unsplash.com/photo-1585515320310-259814833e62?w=640&h=480&fit=crop', // Blender
                'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=640&h=480&fit=crop', // Lamp
                'https://images.unsplash.com/photo-1538688525198-9b88f6f53126?w=640&h=480&fit=crop', // Storage box
                'https://images.unsplash.com/photo-1615874959474-d609969a20ed?w=640&h=480&fit=crop', // Home decor
                'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?w=640&h=480&fit=crop', // Kitchen
                'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=640&h=480&fit=crop', // Furniture
                'https://images.unsplash.com/photo-1484101403633-562f891dc89a?w=640&h=480&fit=crop', // Vase
            ],
            'books' => [
                'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=640&h=480&fit=crop', // Books stack
                'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=640&h=480&fit=crop', // Open book
                'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=640&h=480&fit=crop', // Book shelf
                'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=640&h=480&fit=crop', // Reading book
                'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=640&h=480&fit=crop', // Book cover
                'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=640&h=480&fit=crop', // Book stack 2
                'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=640&h=480&fit=crop', // Library
                'https://images.unsplash.com/photo-1516979187457-637abb4f9353?w=640&h=480&fit=crop', // Vintage books
            ],
            'toys-hobbies' => [
                'https://images.unsplash.com/photo-1558060370-d644479cb6f7?w=640&h=480&fit=crop', // Building blocks
                'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=640&h=480&fit=crop', // Toy car
                'https://images.unsplash.com/photo-1588239034647-25783cbfca30?w=640&h=480&fit=crop', // Puzzle
                'https://images.unsplash.com/photo-1563396983906-b3795482a59a?w=640&h=480&fit=crop', // Teddy bear
                'https://images.unsplash.com/photo-1566694271453-390536dd1f0d?w=640&h=480&fit=crop', // Board game
                'https://images.unsplash.com/photo-1611604548018-d56bbd85d681?w=640&h=480&fit=crop', // Lego
                'https://images.unsplash.com/photo-1587563871167-1ee9c731aefb?w=640&h=480&fit=crop', // Toys
                'https://images.unsplash.com/photo-1560072810-1cffb09faf0f?w=640&h=480&fit=crop', // Game console
            ],
            'beauty' => [
                'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=640&h=480&fit=crop', // Skincare
                'https://images.unsplash.com/photo-1571875257727-256c39da42af?w=640&h=480&fit=crop', // Makeup
                'https://images.unsplash.com/photo-1556228578-8c89e6adf883?w=640&h=480&fit=crop', // Perfume
                'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?w=640&h=480&fit=crop', // Cosmetics
                'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=640&h=480&fit=crop', // Beauty products
                'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=640&h=480&fit=crop', // Spa
                'https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?w=640&h=480&fit=crop', // Skincare bottle
                'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=640&h=480&fit=crop', // Beauty kit
            ],
        ];

        $name = fake()->randomElement($productNames) . ' ' . fake()->numberBetween(1, 99);
        $category = Category::inRandomOrder()->first();
        $categorySlug = $category?->slug ?? 'electronics';

        // Get a random image from the appropriate category
        $images = $categoryImages[$categorySlug] ?? $categoryImages['electronics'];
        $imageUrl = fake()->randomElement($images);

        return [
            'seller_id' => User::where('role', 'seller')->inRandomOrder()->first()?->id ?? User::factory()->seller(),
            'category_id' => $category?->id ?? Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->paragraphs(3, true),
            'price' => fake()->randomFloat(2, 9.99, 999.99),
            'stock_quantity' => fake()->numberBetween(0, 500),
            'image_url' => $imageUrl,
        ];
    }
}

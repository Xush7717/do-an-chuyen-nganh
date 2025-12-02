<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test buyer user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'buyer',
        ]);

        // Create 10 additional buyers
        User::factory(10)->create();

        // Create test seller user
        $testSeller = User::factory()->seller()->create([
            'name' => 'Test Seller',
            'email' => 'seller@example.com',
            'role' => 'seller',
        ]);

        // Create seller profile for test seller
        Seller::factory()->create([
            'user_id' => $testSeller->id,
            'shop_name' => 'TechPro Official',
            'description' => 'Your trusted source for premium tech products and gadgets. We offer the latest in technology with excellent customer service.',
            'logo_url' => fake()->imageUrl(200, 200, 'business', true, 'TechPro'),
        ]);

        // Create 4 additional seller users
        $sellers = User::factory(4)->seller()->create();

        // Create seller profiles for additional sellers
        foreach ($sellers as $seller) {
            Seller::factory()->create([
                'user_id' => $seller->id,
            ]);
        }

        // Create specific categories
        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics'],
            ['name' => 'Fashion', 'slug' => 'fashion'],
            ['name' => 'Home & Living', 'slug' => 'home-living'],
            ['name' => 'Books', 'slug' => 'books'],
            ['name' => 'Toys', 'slug' => 'toys'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Create 50 products distributed among sellers and categories
        Product::factory(50)->create();
    }
}

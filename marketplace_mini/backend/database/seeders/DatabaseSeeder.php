<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@marketmini.com',
            'role' => 'admin',
        ]);

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
            'logo_url' => 'https://images.unsplash.com/photo-1557683316-973673baf926?w=200&h=200&fit=crop',
        ]);

        // Create 4 additional seller users
        $sellers = User::factory(4)->seller()->create();

        // Create seller profiles for additional sellers
        foreach ($sellers as $seller) {
            Seller::factory()->create([
                'user_id' => $seller->id,
            ]);
        }

        // Create specific categories with icons
        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics', 'icon_class' => 'carbon:laptop'],
            ['name' => 'Fashion', 'slug' => 'fashion', 'icon_class' => 'ph:t-shirt'],
            ['name' => 'Home & Living', 'slug' => 'home-living', 'icon_class' => 'carbon:home'],
            ['name' => 'Books', 'slug' => 'books', 'icon_class' => 'carbon:book'],
            ['name' => 'Toys & Hobbies', 'slug' => 'toys-hobbies', 'icon_class' => 'carbon:game-console'],
            ['name' => 'Beauty', 'slug' => 'beauty', 'icon_class' => 'carbon:face-activated'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Create 50 products distributed among sellers and categories
        Product::factory(50)->create();
    }
}

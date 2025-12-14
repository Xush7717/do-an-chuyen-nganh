<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Seller>
 */
class SellerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $shopNames = [
            'TechGadgets Hub',
            'Fashion Avenue',
            'HomeComfort Store',
            'BookWorm Paradise',
            'Toy Kingdom',
            'Electronics World',
            'StyleCraft Boutique',
            'Smart Living',
            'Digital Dreams',
            'Modern Essentials',
        ];

        // High-quality shop logo images from Unsplash (abstract, patterns, brand-like)
        $logoImages = [
            'https://images.unsplash.com/photo-1557683316-973673baf926?w=200&h=200&fit=crop', // Gradient
            'https://images.unsplash.com/photo-1557682224-5b8590cd9ec5?w=200&h=200&fit=crop', // Gradient 2
            'https://images.unsplash.com/photo-1557682250-33bd709cbe85?w=200&h=200&fit=crop', // Abstract
            'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=200&h=200&fit=crop', // Pattern
            'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=200&h=200&fit=crop', // Abstract 2
            'https://images.unsplash.com/photo-1561336313-0bd5e0b27ec8?w=200&h=200&fit=crop', // Colorful
            'https://images.unsplash.com/photo-1579546929518-9e396f3cc809?w=200&h=200&fit=crop', // Gradient 3
            'https://images.unsplash.com/photo-1634017839464-5c339ebe3cb4?w=200&h=200&fit=crop', // Modern
            'https://images.unsplash.com/photo-1541701494587-cb58502866ab?w=200&h=200&fit=crop', // Texture
            'https://images.unsplash.com/photo-1553095066-5014bc7b7f2d?w=200&h=200&fit=crop', // Geometric
        ];

        return [
            'shop_name' => fake()->unique()->randomElement($shopNames),
            'description' => fake()->paragraph(3),
            'logo_url' => fake()->randomElement($logoImages),
        ];
    }
}

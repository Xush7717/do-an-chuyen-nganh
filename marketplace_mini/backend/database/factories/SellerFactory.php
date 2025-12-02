<?php

namespace Database\Factories;

use App\Models\User;
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

        return [
            'shop_name' => fake()->unique()->randomElement($shopNames),
            'description' => fake()->paragraph(3),
            'logo_url' => fake()->imageUrl(200, 200, 'business', true, 'shop'),
        ];
    }
}

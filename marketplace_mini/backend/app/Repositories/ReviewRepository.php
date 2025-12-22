<?php

namespace App\Repositories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Collection;

class ReviewRepository
{
    public function create(array $data): Review
    {
        return Review::create($data);
    }

    public function getByProduct(int $productId): Collection
    {
        return Review::where('product_id', $productId)
            ->with('user:id,name')
            ->latest()
            ->get();
    }

    public function findByUserAndProduct(int $userId, int $productId): ?Review
    {
        return Review::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();
    }

    public function getAverageRating(int $productId): float
    {
        return (float) Review::where('product_id', $productId)
            ->avg('rating') ?? 0.0;
    }

    public function getReviewCount(int $productId): int
    {
        return Review::where('product_id', $productId)->count();
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\ReviewRepository;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function __construct(
        private ReviewRepository $reviewRepository
    ) {}

    public function canUserReviewProduct(int $userId, int $productId): bool
    {
        return Order::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereHas('orderItems', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->exists();
    }

    public function createReview(int $userId, int $productId, int $rating, ?string $comment): array
    {
        if (!$this->canUserReviewProduct($userId, $productId)) {
            return [
                'success' => false,
                'message' => 'You can only review products you have purchased and received.',
            ];
        }

        $existingReview = $this->reviewRepository->findByUserAndProduct($userId, $productId);
        if ($existingReview) {
            return [
                'success' => false,
                'message' => 'You have already reviewed this product.',
            ];
        }

        $review = $this->reviewRepository->create([
            'user_id' => $userId,
            'product_id' => $productId,
            'rating' => $rating,
            'comment' => $comment,
        ]);

        // Load the user relationship before returning
        $review->load('user:id,name');

        return [
            'success' => true,
            'review' => $review,
        ];
    }

    public function getProductReviews(int $productId)
    {
        return $this->reviewRepository->getByProduct($productId);
    }

    public function getProductRatingStats(int $productId): array
    {
        return [
            'average_rating' => round($this->reviewRepository->getAverageRating($productId), 1),
            'review_count' => $this->reviewRepository->getReviewCount($productId),
        ];
    }
}

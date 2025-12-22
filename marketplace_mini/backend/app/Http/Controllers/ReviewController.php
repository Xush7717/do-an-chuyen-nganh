<?php

namespace App\Http\Controllers;

use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService
    ) {}

    public function index(int $productId): JsonResponse
    {
        $reviews = $this->reviewService->getProductReviews($productId);
        $stats = $this->reviewService->getProductRatingStats($productId);

        return response()->json([
            'reviews' => $reviews,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->reviewService->createReview(
            $request->user()->id,
            $validated['product_id'],
            $validated['rating'],
            $validated['comment'] ?? null
        );

        if (!$result['success']) {
            // Return 422 for duplicate reviews, 403 for unauthorized
            $statusCode = str_contains($result['message'], 'already reviewed') ? 422 : 403;
            return response()->json([
                'message' => $result['message'],
            ], $statusCode);
        }

        return response()->json([
            'message' => 'Review submitted successfully.',
            'review' => $result['review'],
        ], 201);
    }

    public function canReview(Request $request, int $productId): JsonResponse
    {
        try {
            $canReview = $this->reviewService->canUserReviewProduct(
                $request->user()->id,
                $productId
            );

            return response()->json([
                'can_review' => $canReview,
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking review eligibility', [
                'user_id' => $request->user()->id,
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'can_review' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

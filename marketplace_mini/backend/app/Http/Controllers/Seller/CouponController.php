<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        private CouponService $couponService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $coupons = $this->couponService->getSellerCoupons($request->user()->id);

        return response()->json([
            'coupons' => $coupons,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash'],
            'type' => ['required', 'in:fixed,percentage'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['required', 'date', 'after:today'],
        ]);

        if ($validated['type'] === 'percentage' && $validated['value'] > 100) {
            return response()->json([
                'message' => 'Percentage discount cannot exceed 100%.',
            ], 422);
        }

        $result = $this->couponService->createCoupon($request->user()->id, $validated);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'message' => 'Coupon created successfully.',
            'coupon' => $result['coupon'],
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $result = $this->couponService->deleteCoupon($request->user()->id, $id);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'message' => $result['message'],
        ]);
    }
}

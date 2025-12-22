<?php

namespace App\Http\Controllers;

use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        private CouponService $couponService
    ) {}

    /**
     * Get available coupons for cart items
     */
    public function available(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_items' => ['required', 'array', 'min:1'],
            'cart_items.*.product_id' => ['required', 'exists:products,id'],
            'cart_items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $availableCoupons = $this->couponService->getAvailableCoupons(
            $validated['cart_items']
        );

        return response()->json([
            'success' => true,
            'coupons' => $availableCoupons,
        ]);
    }

    /**
     * Apply a coupon to cart items
     */
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'cart_items' => ['required', 'array', 'min:1'],
            'cart_items.*.product_id' => ['required', 'exists:products,id'],
            'cart_items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->couponService->validateAndApplyCoupon(
            $validated['code'],
            $validated['cart_items']
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Coupon applied successfully.',
            'coupon' => [
                'code' => $result['coupon']->code,
                'type' => $result['coupon']->type,
                'value' => $result['coupon']->value,
                'seller_id' => $result['seller_id'],
                'discount_amount' => $result['discount_amount'],
                'applicable_subtotal' => $result['applicable_subtotal'],
            ],
        ]);
    }
}

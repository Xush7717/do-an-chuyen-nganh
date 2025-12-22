<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\CouponRepository;
use Carbon\Carbon;

class CouponService
{
    public function __construct(
        private CouponRepository $couponRepository
    ) {}

    public function validateAndApplyCoupon(string $code, array $cartItems): array
    {
        $coupon = $this->couponRepository->findByCode($code);

        if (!$coupon) {
            return [
                'success' => false,
                'message' => 'Invalid coupon code.',
            ];
        }

        if ($coupon->expires_at && Carbon::parse($coupon->expires_at)->isPast()) {
            return [
                'success' => false,
                'message' => 'This coupon has expired.',
            ];
        }

        if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
            return [
                'success' => false,
                'message' => 'This coupon has reached its usage limit.',
            ];
        }

        $productIds = array_column($cartItems, 'product_id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $sellerSubtotal = 0;
        foreach ($cartItems as $item) {
            $product = $products->get($item['product_id']);
            if ($product && $product->seller_id == $coupon->seller_id) {
                $quantity = $item['quantity'] ?? 1;
                $sellerSubtotal += $product->price * $quantity;
            }
        }

        if ($sellerSubtotal == 0) {
            return [
                'success' => false,
                'message' => 'This coupon does not apply to any items in your cart.',
            ];
        }

        if ($sellerSubtotal < $coupon->min_order_value) {
            return [
                'success' => false,
                'message' => sprintf(
                    'Minimum order value of $%.2f required for this coupon.',
                    $coupon->min_order_value
                ),
            ];
        }

        $discountAmount = $this->calculateDiscount($coupon, $sellerSubtotal);

        return [
            'success' => true,
            'coupon' => $coupon,
            'discount_amount' => round($discountAmount, 2),
            'seller_id' => $coupon->seller_id,
            'applicable_subtotal' => round($sellerSubtotal, 2),
        ];
    }

    private function calculateDiscount($coupon, float $subtotal): float
    {
        if ($coupon->type === 'fixed') {
            return min($coupon->value, $subtotal);
        }

        return ($subtotal * $coupon->value) / 100;
    }

    public function getSellerCoupons(int $sellerId)
    {
        return $this->couponRepository->getSellerCoupons($sellerId);
    }

    /**
     * Get available coupons for cart items grouped by seller
     */
    public function getAvailableCoupons(array $cartItems): array
    {
        // Get products to determine sellers and calculate subtotals
        $productIds = array_column($cartItems, 'product_id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        // Group cart items by seller and calculate subtotals
        $sellerSubtotals = [];
        foreach ($cartItems as $item) {
            $product = $products->get($item['product_id']);
            if (!$product) {
                continue;
            }

            $sellerId = $product->seller_id;
            $quantity = $item['quantity'] ?? 1;
            $itemTotal = $product->price * $quantity;

            if (!isset($sellerSubtotals[$sellerId])) {
                $sellerSubtotals[$sellerId] = [
                    'subtotal' => 0,
                    'seller_name' => $product->seller->name ?? 'Unknown Seller',
                ];
            }

            $sellerSubtotals[$sellerId]['subtotal'] += $itemTotal;
        }

        if (empty($sellerSubtotals)) {
            return [];
        }

        // Get active coupons for these sellers
        $sellerIds = array_keys($sellerSubtotals);
        $coupons = $this->couponRepository->getActiveCouponsBySellers($sellerIds);

        // Group coupons by seller and filter by min_order_value
        $availableCoupons = [];
        foreach ($coupons as $coupon) {
            $sellerId = $coupon->seller_id;
            $sellerSubtotal = $sellerSubtotals[$sellerId]['subtotal'] ?? 0;

            // Check if subtotal meets minimum order value
            if ($sellerSubtotal >= $coupon->min_order_value) {
                // Calculate potential discount
                $discountAmount = $this->calculateDiscount($coupon, $sellerSubtotal);

                if (!isset($availableCoupons[$sellerId])) {
                    $availableCoupons[$sellerId] = [
                        'seller_id' => $sellerId,
                        'seller_name' => $sellerSubtotals[$sellerId]['seller_name'],
                        'subtotal' => $sellerSubtotal,
                        'coupons' => [],
                    ];
                }

                $availableCoupons[$sellerId]['coupons'][] = [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'min_order_value' => $coupon->min_order_value,
                    'expires_at' => $coupon->expires_at?->format('Y-m-d H:i:s'),
                    'discount_amount' => round($discountAmount, 2),
                ];
            }
        }

        return array_values($availableCoupons);
    }

    public function createCoupon(int $sellerId, array $data): array
    {
        $existingCoupon = $this->couponRepository->findByCode($data['code']);
        if ($existingCoupon) {
            return [
                'success' => false,
                'message' => 'Coupon code already exists.',
            ];
        }

        $coupon = $this->couponRepository->create([
            'seller_id' => $sellerId,
            'code' => strtoupper($data['code']),
            'type' => $data['type'],
            'value' => $data['value'],
            'min_order_value' => $data['min_order_value'] ?? 0,
            'usage_limit' => $data['usage_limit'] ?? null,
            'expires_at' => $data['expires_at'],
        ]);

        return [
            'success' => true,
            'coupon' => $coupon,
        ];
    }

    public function deleteCoupon(int $sellerId, int $couponId): array
    {
        $coupon = $this->couponRepository->findBySeller($sellerId, $couponId);

        if (!$coupon) {
            return [
                'success' => false,
                'message' => 'Coupon not found or does not belong to you.',
            ];
        }

        $this->couponRepository->delete($coupon);

        return [
            'success' => true,
            'message' => 'Coupon deleted successfully.',
        ];
    }
}

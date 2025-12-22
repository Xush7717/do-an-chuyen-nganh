<?php

namespace App\Repositories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Collection;

class CouponRepository
{
    public function findByCode(string $code): ?Coupon
    {
        return Coupon::where('code', $code)->first();
    }

    public function getSellerCoupons(int $sellerId): Collection
    {
        return Coupon::where('seller_id', $sellerId)
            ->latest()
            ->get();
    }

    public function create(array $data): Coupon
    {
        return Coupon::create($data);
    }

    public function update(Coupon $coupon, array $data): bool
    {
        return $coupon->update($data);
    }

    public function delete(Coupon $coupon): bool
    {
        return $coupon->delete();
    }

    public function incrementUsage(Coupon $coupon): void
    {
        $coupon->increment('usage_count');
    }

    public function findBySeller(int $sellerId, int $couponId): ?Coupon
    {
        return Coupon::where('id', $couponId)
            ->where('seller_id', $sellerId)
            ->first();
    }

    /**
     * Get active coupons for multiple sellers
     */
    public function getActiveCouponsBySellers(array $sellerIds): Collection
    {
        return Coupon::whereIn('seller_id', $sellerIds)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('usage_limit')
                    ->orWhereRaw('usage_count < usage_limit');
            })
            ->with('seller:id,name')
            ->get();
    }
}

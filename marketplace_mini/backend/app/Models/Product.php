<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock_quantity',
        'image_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    protected $appends = [
        'average_rating',
        'review_count',
    ];

    /**
     * Accessor to transform image_url to full URL
     * - External URLs (http/https): Return as-is
     * - Local paths: Prepend backend URL
     */
    public function getImageUrlAttribute($value)
    {
        if (! $value) {
            return null;
        }

        // If already a full URL (external), return as-is
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        // Local path: Transform to full URL
        // If path starts with /storage/, use it directly
        // Otherwise, prepend /storage/
        if (str_starts_with($value, '/storage/')) {
            return asset($value);
        }

        return asset('storage/'.$value);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getAverageRatingAttribute(): float
    {
        return round((float) $this->reviews()->avg('rating') ?? 0.0, 1);
    }

    public function getReviewCountAttribute(): int
    {
        return $this->reviews()->count();
    }
}

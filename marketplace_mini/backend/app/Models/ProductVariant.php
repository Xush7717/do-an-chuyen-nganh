<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'stock_quantity',
        'image_url',
        'attributes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'attributes' => 'array',
    ];

    /**
     * Boot the model and register event listeners.
     * Automatically sync parent product's stock_quantity whenever a variant is saved or deleted.
     */
    protected static function booted(): void
    {
        // Listen to the 'saved' event (covers create, update, and decrement operations)
        static::saved(function (ProductVariant $variant) {
            static::syncParentStock($variant);
        });

        // Listen to the 'deleted' event to recalculate stock when a variant is removed
        static::deleted(function (ProductVariant $variant) {
            static::syncParentStock($variant);
        });
    }

    /**
     * Sync the parent product's total stock quantity.
     * Calculates the sum of all variant stock quantities and updates the parent.
     */
    protected static function syncParentStock(ProductVariant $variant): void
    {
        // Only sync if the variant has a parent product
        if (! $variant->product) {
            return;
        }

        // Calculate total stock from all variants
        $totalStock = $variant->product->variants()->sum('stock_quantity');

        // Update parent product's stock_quantity
        // Use updateQuietly to prevent triggering additional events
        $variant->product->updateQuietly([
            'stock_quantity' => $totalStock,
        ]);
    }

    public function getImageUrlAttribute($value)
    {
        if (! $value) {
            return $this->product->image_url ?? null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        if (str_starts_with($value, '/storage/')) {
            return asset($value);
        }

        return asset('storage/'.$value);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}

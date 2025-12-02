<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'expires_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expires_at' => 'date',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}

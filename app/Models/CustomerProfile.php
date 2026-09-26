<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerProfile extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerProfileFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'address',
        'profile_image',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class, 'customer_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function favoriteProducts(): HasMany
    {
        return $this->hasMany(FavoriteProduct::class, 'customer_id');
    }

    public function favoriteFarmers(): HasMany
    {
        return $this->hasMany(FavoriteFarmer::class, 'customer_id');
    }

    public function favoriteMarkets(): HasMany
    {
        return $this->hasMany(FavoriteMarket::class, 'customer_id');
    }

    public function productReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'customer_id');
    }

    public function farmerReviews(): HasMany
    {
        return $this->hasMany(FarmerReview::class, 'customer_id');
    }
}

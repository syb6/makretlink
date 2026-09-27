<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Market extends Model
{
    /** @use HasFactory<\Database\Factories\MarketFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'map_provider',
        'description',
        'image',
        'status',
    ];

    /** Market photo if uploaded, otherwise a fixed local fallback. */
    public function getImageUrlAttribute(): string
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : asset('images/placeholders/market.svg');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(MarketSchedule::class);
    }

    public function farmerMarkets(): HasMany
    {
        return $this->hasMany(FarmerMarket::class);
    }

    public function favoriteMarkets(): HasMany
    {
        return $this->hasMany(FavoriteMarket::class);
    }
}

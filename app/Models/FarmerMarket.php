<?php

namespace App\Models;

use Database\Factories\FarmerMarketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FarmerMarket extends Model
{
    /** @use HasFactory<FarmerMarketFactory> */
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'market_id',
        'stall_name',
        'stall_location',
        'latitude',
        'longitude',
        'status',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_id');
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(FarmerMarketSchedule::class);
    }

    public function stockTemplates(): HasMany
    {
        return $this->hasMany(StockTemplate::class);
    }

    public function weeklyStocks(): HasMany
    {
        return $this->hasMany(WeeklyStock::class);
    }

    public function pickupSlots(): HasMany
    {
        return $this->hasMany(PickupSlot::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}

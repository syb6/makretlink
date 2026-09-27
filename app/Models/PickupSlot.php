<?php

namespace App\Models;

use Database\Factories\PickupSlotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PickupSlot extends Model
{
    /** @use HasFactory<PickupSlotFactory> */
    use HasFactory;

    protected $fillable = [
        'farmer_market_id',
        'pickup_date',
        'start_time',
        'end_time',
        'cutoff_at',
        'max_orders',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'pickup_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'cutoff_at' => 'datetime',
        ];
    }

    public function farmerMarket(): BelongsTo
    {
        return $this->belongsTo(FarmerMarket::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isBookable(): bool
    {
        return $this->status === 'active'
            && now()->lessThan($this->cutoff_at)
            && ($this->max_orders === null || $this->orders()->count() < $this->max_orders);
    }
}

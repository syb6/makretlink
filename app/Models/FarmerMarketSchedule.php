<?php

namespace App\Models;

use Database\Factories\FarmerMarketScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmerMarketSchedule extends Model
{
    /** @use HasFactory<FarmerMarketScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'farmer_market_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'is_active' => 'boolean',
        ];
    }

    public function farmerMarket(): BelongsTo
    {
        return $this->belongsTo(FarmerMarket::class);
    }
}

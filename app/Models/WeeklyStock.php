<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class WeeklyStock extends Model
{
    /** @use HasFactory<\Database\Factories\WeeklyStockFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'farmer_market_id',
        'stock_template_id',
        'week_start',
        'quantity',
        'available_quantity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function farmerMarket(): BelongsTo
    {
        return $this->belongsTo(FarmerMarket::class);
    }

    public function stockTemplate(): BelongsTo
    {
        return $this->belongsTo(StockTemplate::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available'
            && $this->available_quantity > 0
            && $this->week_start->isSameWeek(now(), Carbon::SUNDAY);
    }
}

<?php

namespace App\Models;

use Database\Factories\StockTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTemplate extends Model
{
    /** @use HasFactory<StockTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'farmer_market_id',
        'default_quantity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    public function weeklyStocks(): HasMany
    {
        return $this->hasMany(WeeklyStock::class);
    }
}

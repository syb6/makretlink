<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FavoriteMarket extends Model
{
    /** @use HasFactory<\Database\Factories\FavoriteMarketFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'market_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_id');
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}

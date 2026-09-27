<?php

namespace App\Models;

use Database\Factories\FavoriteProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FavoriteProduct extends Model
{
    /** @use HasFactory<FavoriteProductFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'product_id',
        'notify_on_restock',
    ];

    protected function casts(): array
    {
        return [
            'notify_on_restock' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

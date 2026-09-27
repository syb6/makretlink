<?php

namespace App\Models;

use Database\Factories\FarmerReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmerReview extends Model
{
    /** @use HasFactory<FarmerReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'farmer_id',
        'order_id',
        'rating',
        'comment',
        'farmer_reply',
        'replied_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_id');
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

<?php

namespace App\Models;

use Database\Factories\FavoriteFarmerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FavoriteFarmer extends Model
{
    /** @use HasFactory<FavoriteFarmerFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'farmer_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_id');
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_id');
    }
}

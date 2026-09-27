<?php

namespace App\Models;

use Database\Factories\FarmerProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmerProfile extends Model
{
    /** @use HasFactory<FarmerProfileFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'business_name',
        'contact_person',
        'address',
        'description',
        'profile_image',
        'approval_status',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'farmer_id');
    }

    public function farmerMarkets(): HasMany
    {
        return $this->hasMany(FarmerMarket::class, 'farmer_id');
    }

    public function favoriteFarmers(): HasMany
    {
        return $this->hasMany(FavoriteFarmer::class, 'farmer_id');
    }

    public function farmerReviews(): HasMany
    {
        return $this->hasMany(FarmerReview::class, 'farmer_id');
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }
}

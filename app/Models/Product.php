<?php

namespace App\Models;

use App\Services\ImageLibrary;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farmer_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'unit',
        'image',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    /**
     * URL to the product's picture straight from its `image` column
     * (a path inside public/images/placeholders/product-images/),
     * falling back to the category's picture, then the generic one.
     */
    public function getImageUrlAttribute(): string
    {
        return ImageLibrary::url('product', $this->image)
            ?? $this->category?->image_url
            ?? asset('images/placeholders/general.png');
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockTemplates(): HasMany
    {
        return $this->hasMany(StockTemplate::class);
    }

    public function weeklyStocks(): HasMany
    {
        return $this->hasMany(WeeklyStock::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function favoriteProducts(): HasMany
    {
        return $this->hasMany(FavoriteProduct::class);
    }

    public function productReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}

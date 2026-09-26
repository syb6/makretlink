<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
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
     * Placeholder image key derived from the product's category.
     * Falls back to a stable per-product pick so results never look random.
     */
    public function getPlaceholderKeyAttribute(): string
    {
        $map = [
            'vegetables'      => 'vegetables',
            'fruits'          => 'fruits',
            'dairy-eggs'      => 'dairy-eggs',
            'baked-goods'     => 'baked-goods',
            'herbs-greens'    => 'herbs-greens',
            'honey-preserves' => 'honey-preserves',
        ];

        return $map[$this->category?->slug] ?? 'general';
    }

    /**
     * URL to the real uploaded image, or a deterministic local placeholder.
     * Local SVGs avoid external requests (SRS: content licensing constraints)
     * and keep the UI identical in light and dark mode.
     */
    public function getImageUrlAttribute(): string
    {
        return $this->image
            ? asset('storage/'.$this->image)
            : asset('images/placeholders/'.$this->placeholder_key.'.svg');
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

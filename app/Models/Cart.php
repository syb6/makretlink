<?php

namespace App\Models;

use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function total(): float
    {
        return (float) $this->items->sum(fn (CartItem $item) => $item->quantity * $item->price);
    }

    /** Number of distinct items in the given user's cart (0 if none). */
    public static function countFor(User $user): int
    {
        $customer = $user->customerProfile;

        if (! $customer) {
            return 0;
        }

        return (int) CartItem::whereHas('cart', fn ($q) => $q->where('customer_id', $customer->id))->count();
    }
}

<?php

namespace App\Models;

use Database\Factories\WeeklyStockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class WeeklyStock extends Model
{
    /** @use HasFactory<WeeklyStockFactory> */
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

    /**
     * Stock a customer is allowed to order, right now. One shared definition
     * so cart, checkout and every public listing enforce the same rules:
     * stock row is available with quantity left, the week is current or
     * upcoming (old weeks can't be ordered), the product is active, the
     * farmer is approved, and the stall AND its market are active.
     */
    public function scopeSellable($query)
    {
        return $query
            ->where('status', 'available')
            ->where('available_quantity', '>', 0)
            ->where('week_start', '>=', now()->startOfWeek(Carbon::SUNDAY)->toDateString())
            ->whereHas('product', fn ($p) => $p
                ->where('status', 'active')
                ->whereHas('farmer', fn ($f) => $f->where('approval_status', 'approved')))
            ->whereHas('farmerMarket', fn ($fm) => $fm
                ->where('status', 'active')
                ->whereHas('market', fn ($m) => $m->where('status', 'active')));
    }

    /**
     * Distinct active markets with live stock per product, for a set of stock
     * rows. Cards use this to say "also at 2 more markets" so a product sold
     * at multiple markets doesn't read as a duplicate listing.
     *
     * @param  iterable<int, self>  $stocks
     * @return array<int, int>  product_id => market count
     */
    public static function marketCountsFor(iterable $stocks): array
    {
        $productIds = collect($stocks)
            ->pluck('product_id')
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return [];
        }

        return self::query()
            ->whereIn('product_id', $productIds)
            ->where('status', 'available')
            ->where('available_quantity', '>', 0)
            ->where('week_start', '>=', now()->startOfWeek(Carbon::SUNDAY)->toDateString())
            ->whereHas('farmerMarket', fn ($fm) => $fm
                ->where('status', 'active')
                ->whereHas('market', fn ($m) => $m->where('status', 'active')))
            ->selectRaw('product_id, COUNT(DISTINCT farmer_market_id) as markets_count')
            ->groupBy('product_id')
            ->pluck('markets_count', 'product_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}

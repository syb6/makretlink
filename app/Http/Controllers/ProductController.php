<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\FavoriteProduct;
use App\Models\Market;
use App\Models\MarketSchedule;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\WeeklyStock;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');
        $category = $request->query('category');
        $market = $request->query('market');
        $day = $request->query('day');
        $min = $request->query('min_price');
        $max = $request->query('max_price');
        $sort = $request->query('sort', 'name');

        $stocks = WeeklyStock::query()
            ->where('status', 'available')
            ->where('available_quantity', '>', 0)
            // Only current or upcoming weeks — stale stock from past weeks must not be sellable.
            ->where('week_start', '>=', now()->startOfWeek(Carbon::SUNDAY)->toDateString())
            ->with(['product.category', 'product.farmer.user', 'farmerMarket.market'])
            ->whereHas('product', fn ($p) => $p
                ->where('status', 'active')
                // Pending/rejected farmers must never appear in public listings.
                ->whereHas('farmer', fn ($f) => $f->where('approval_status', 'approved'))
                ->when($search, fn ($q) => $q->where(fn ($w) => $w
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")))
                ->when($category, fn ($q) => $q->where('category_id', $category))
                ->when($min !== null && $min !== '', fn ($q) => $q->where('price', '>=', $min))
                ->when($max !== null && $max !== '', fn ($q) => $q->where('price', '<=', $max)))
            ->when($market, fn ($q) => $q->where('farmer_market_id', $market))
            ->when($day !== null && $day !== '', fn ($q) => $q->whereHas('farmerMarket.schedules', fn ($s) => $s
                ->where('day_of_week', (int) $day)
                ->where('is_active', true)))
            ->whereHas('farmerMarket', fn ($fm) => $fm
                ->where('status', 'active')
                ->whereHas('market', fn ($m) => $m->where('status', 'active')))
            ->get()
            // One row per product+stall so a product stocked at two markets shows twice, not N times.
            ->unique(fn ($s) => $s->product_id.'-'.$s->farmer_market_id)
            ->values();

        // Sort on the deduped collection. Sorting via a SQL join would break the
        // paginator row identity (model IDs overwritten by join columns) and
        // make "Add to cart" post the wrong stock.
        $stocks = match ($sort) {
            'price_low' => $stocks->sortBy(fn ($s) => (float) $s->product->price)->values(),
            'price_high' => $stocks->sortByDesc(fn ($s) => (float) $s->product->price)->values(),
            'newest' => $stocks->sortByDesc(fn ($s) => $s->product->created_at)->values(),
            default => $stocks->sortBy(fn ($s) => mb_strtolower($s->product->name))->values(),
        };

        // Manual pagination over the deduped collection
        $perPage = 12;
        $page = max(1, (int) $request->query('page', 1));
        $chunk = $stocks->slice(($page - 1) * $perPage, $perPage)->values();

        // One query for the customer's favorite products instead of one per card
        // (N+1 across the grid — the set is passed into the card partial).
        $favoriteProductIds = collect();
        if ($request->user()?->isCustomer()) {
            $favoriteProductIds = FavoriteProduct::where('customer_id', $request->user()->customerProfile->id)
                ->pluck('product_id');
        }

        return view('products.index', [
            'stocks' => new LengthAwarePaginator(
                $chunk,
                $stocks->count(),
                $perPage,
                $page,
                ['path' => url()->current(), 'query' => $request->query()]
            ),
            'categories' => Category::where('status', 'active')->orderBy('name')->get(),
            'markets' => Market::where('status', 'active')->orderBy('name')->get(),
            'days' => MarketSchedule::DAYS,
            'filters' => compact('search', 'category', 'market', 'day', 'min', 'max', 'sort'),
            'favoriteProductIds' => $favoriteProductIds,
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'active', 404);

        $product->load(['category', 'farmer.user', 'farmer.farmerMarkets.market']);

        $stocks = WeeklyStock::query()
            ->where('product_id', $product->id)
            ->where('status', 'available')
            ->where('available_quantity', '>', 0)
            ->with('farmerMarket.market')
            ->get();

        $reviews = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('status', 'visible')
            ->with('customer.user')
            ->latest()
            ->take(6)
            ->get();

        $avgRating = (float) ProductReview::where('product_id', $product->id)->where('status', 'visible')->avg('rating');

        $reviewCount = (int) ProductReview::where('product_id', $product->id)->where('status', 'visible')->count();

        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->take(4)
            ->get();

        return view('products.show', compact('product', 'stocks', 'reviews', 'avgRating', 'reviewCount', 'related'));
    }

    /**
     * JSON used by product card "Add to cart" modal.
     */
    public function stockOptions(Product $product)
    {
        $options = WeeklyStock::where('product_id', $product->id)
            ->where('status', 'available')
            ->where('available_quantity', '>', 0)
            ->with('farmerMarket.market')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'label' => $s->farmerMarket->market->name.' — '.($s->farmerMarket->stall_name ?? $s->product->farmer->business_name),
                'available' => (float) $s->available_quantity,
                'unit' => $s->product->unit,
                'price' => (float) $s->product->price,
            ]);

        return response()->json($options);
    }
}

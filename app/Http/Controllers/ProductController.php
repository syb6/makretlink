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
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Filter inputs are sanitized before touching the query builder so
        // malformed query strings can't probe SQL or skew the listing. safe()
        // silently drops anything invalid — a tampered URL simply shows the
        // default listing instead of an error page.
        $validated = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'market' => ['nullable', 'integer', 'exists:markets,id'],
            'day' => ['nullable', 'integer', 'between:0,6'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'sort' => ['nullable', 'in:name,price_low,price_high,newest'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ])->safe()->all();

        $search = $validated['q'] ?? null;
        $category = $validated['category'] ?? null;
        $market = $validated['market'] ?? null;
        $day = $validated['day'] ?? null;
        $min = $validated['min_price'] ?? null;
        $max = $validated['max_price'] ?? null;
        $sort = $validated['sort'] ?? 'name';

        $stocks = WeeklyStock::sellable()
            ->with(['product.category', 'product.farmer.user', 'farmerMarket.market'])
            ->whereHas('product', fn ($p) => $p
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
        $page = (int) ($validated['page'] ?? 1);
        $chunk = $stocks->slice(($page - 1) * $perPage, $perPage)->values();

        // One query for the customer's favorite products instead of one per card
        // (N+1 across the grid — the set is passed into the card partial).
        $favoriteProductIds = collect();
        if ($request->user()?->isCustomer()) {
            $favoriteProductIds = FavoriteProduct::where('customer_id', $request->user()->customerProfile->id)
                ->pluck('product_id');
        }

        return view('products.index', [
            // product_id => how many markets stock it — cards show "also at N more markets"
            // so the same product listed per market doesn't read as a duplicate.
            'marketCounts' => WeeklyStock::marketCountsFor($chunk),
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
        // Hidden items must not be reachable by URL: product must be active
        // AND its farmer approved (pending/rejected farmers' products 404).
        abort_unless($product->status === 'active', 404);
        abort_unless($product->farmer->approval_status === 'approved', 404);

        $product->load(['category', 'farmer.user', 'farmer.farmerMarkets.market']);

        // Only sellable stock: current/upcoming week, active stall + market.
        // Listing raw stock rows leaked old weeks and inactive stalls.
        $stocks = WeeklyStock::sellable()
            ->where('product_id', $product->id)
            ->with('farmerMarket.market')
            ->get();

        $reviews = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('status', 'visible')
            ->with('customer.user')
            ->latest()
            ->take(6)
            ->get();

        // SRS 1.6: reviews must come from completed orders; hide the review
        // form on the product page itself (customers review from their order).


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
        // Same sellable rules as the detail page — the add-to-cart modal
        // must not offer old-week or inactive-stall stock either.
        $options = WeeklyStock::sellable()
            ->where('product_id', $product->id)
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

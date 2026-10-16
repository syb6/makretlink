<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\FarmerProfile;
use App\Models\FavoriteFarmer;
use App\Models\FavoriteMarket;
use App\Models\FavoriteProduct;
use App\Models\Market;
use App\Models\MarketSchedule;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\WeeklyStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Read-only JSON feeds for the Angular SPA (public pages).
 *
 * Each method mirrors the data its Blade page shows so the SPA can render an
 * equivalent UI against the exact same business rules (sellable stock scope,
 * dedupe per product+stall, favorite sets resolved in bulk).
 */
class PublicDataController extends Controller
{
    public function home(Request $request): JsonResponse
    {
        $announcements = Announcement::published()->latest('published_at')->take(3)
            ->get(['id', 'title', 'message', 'published_at']);

        $featuredStocks = WeeklyStock::sellable()->with([
            'product.category',
            'product.farmer.user',
            'farmerMarket.market',
        ])->inRandomOrder()->take(8)->get();

        $stats = Cache::remember('home.stats', now()->addMinutes(10), function () {
            return [
                'marketCount' => Market::where('status', 'active')->count(),
                'farmerCount' => FarmerProfile::where('approval_status', 'approved')->count(),
                'productCount' => Product::where('status', 'active')->count(),
                'categories' => Category::where('status', 'active')->orderBy('name')
                    ->get(['id', 'name', 'slug', 'image']),
            ];
        });

        [$favoriteProductIds] = $this->favoriteSets($request, ['product']);

        return response()->json([
            'announcements' => $announcements,
            'featuredStocks' => $featuredStocks->map(fn ($s) => $this->stockCard($s)),
            'marketCounts' => WeeklyStock::marketCountsFor($featuredStocks),
            'marketCount' => $stats['marketCount'],
            'farmerCount' => $stats['farmerCount'],
            'productCount' => $stats['productCount'],
            'categories' => $stats['categories'],
            'favoriteProductIds' => $favoriteProductIds,
            'markets' => Market::query()->with('schedules')
                ->withCount(['farmerMarkets as farmers_count' => fn ($q) => $q->where('status', 'active')])
                ->where('status', 'active')->orderBy('name')->take(3)->get(),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $q = $request->query();
        $search = isset($q['q']) ? trim((string) $q['q']) : null;
        $category = $q['category'] ?? null;
        $market = $q['market'] ?? null;
        $day = $q['day'] ?? null;
        $min = $q['min_price'] ?? null;
        $max = $q['max_price'] ?? null;
        $sort = $q['sort'] ?? 'name';
        $page = max(1, (int) ($q['page'] ?? 1));

        $stocks = WeeklyStock::sellable()
            ->with(['product.category', 'product.farmer.user', 'farmerMarket.market'])
            ->whereHas('product', fn ($p) => $p
                ->when($search, fn ($s) => $s->where(fn ($w) => $w
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")))
                ->when(is_numeric($category), fn ($s) => $s->where('category_id', (int) $category))
                ->when($min !== null && $min !== '', fn ($s) => $s->where('price', '>=', $min))
                ->when($max !== null && $max !== '', fn ($s) => $s->where('price', '<=', $max)))
            ->when(is_numeric($market), fn ($s) => $s->where('farmer_market_id', (int) $market))
            ->when($day !== null && $day !== '', fn ($s) => $s->whereHas('farmerMarket.schedules', fn ($x) => $x
                ->where('day_of_week', (int) $day)->where('is_active', true)))
            ->get()
            ->unique(fn ($s) => $s->product_id.'-'.$s->farmer_market_id)
            ->values();

        $stocks = match ($sort) {
            'price_low' => $stocks->sortBy(fn ($s) => (float) $s->product->price)->values(),
            'price_high' => $stocks->sortByDesc(fn ($s) => (float) $s->product->price)->values(),
            'newest' => $stocks->sortByDesc(fn ($s) => $s->product->created_at)->values(),
            default => $stocks->sortBy(fn ($s) => mb_strtolower($s->product->name))->values(),
        };

        $perPage = 12;
        $chunk = $stocks->slice(($page - 1) * $perPage, $perPage)->values();

        [$favoriteProductIds] = $this->favoriteSets($request, ['product']);

        return response()->json([
            'data' => $chunk->map(fn ($s) => $this->stockCard($s)),
            'total' => $stocks->count(),
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($stocks->count() / $perPage)),
            'marketCounts' => WeeklyStock::marketCountsFor($chunk),
            'categories' => Category::where('status', 'active')->orderBy('name')->get(['id', 'name', 'slug']),
            'markets' => Market::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'days' => MarketSchedule::DAYS,
            'filters' => ['q' => $search, 'category' => $category, 'market' => $market, 'day' => $day,
                'min_price' => $min, 'max_price' => $max, 'sort' => $sort],
            'favoriteProductIds' => $favoriteProductIds,
        ]);
    }

    public function productShow(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->status === 'active', 404);
        abort_unless($product->farmer->approval_status === 'approved', 404);

        $product->load(['category', 'farmer.user', 'farmer.farmerMarkets.market']);

        $stocks = WeeklyStock::sellable()
            ->where('product_id', $product->id)
            ->with('farmerMarket.market')
            ->get();

        $reviews = ProductReview::query()
            ->where('product_id', $product->id)->where('status', 'visible')
            ->with('customer.user')->latest()->take(6)->get();

        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)->where('status', 'active')
            ->get(['id', 'name', 'slug', 'image', 'price', 'unit']);

        [$favoriteProductIds] = $this->favoriteSets($request, ['product']);

        return response()->json([
            'product' => array_merge($product->only([
                'id', 'name', 'slug', 'description', 'price', 'unit', 'image', 'status',
            ]), [
                'category' => $product->category?->only(['id', 'name', 'slug']),
                'farmer' => $this->farmerCard($product->farmer),
            ]),
            'stocks' => $stocks->map(fn ($s) => [
                'id' => $s->id,
                'market' => $s->farmerMarket->market?->only(['id', 'name', 'address']),
                'stall_name' => $s->farmerMarket->stall_name,
                'available' => (float) $s->available_quantity,
                'unit' => $product->unit,
                'price' => (float) $product->price,
            ]),
            'reviews' => $reviews->map(fn ($r) => [
                'id' => $r->id, 'rating' => $r->rating, 'comment' => $r->comment,
                'customer' => $r->customer?->user?->name,
                'created_at' => $r->created_at?->toIso8601String(),
            ]),
            'avgRating' => (float) ProductReview::where('product_id', $product->id)->where('status', 'visible')->avg('rating'),
            'reviewCount' => (int) ProductReview::where('product_id', $product->id)->where('status', 'visible')->count(),
            'related' => $related,
            'favoriteProductIds' => $favoriteProductIds,
        ]);
    }

    /** Mirror of the contact page data: flagship market address + open days. */
    public function contact(): JsonResponse
    {
        $flagship = Market::where('name', 'Green Valley Community Market')
            ->with('schedules')->first();

        return response()->json([
            'flagship' => $flagship ? [
                'id' => $flagship->id,
                'name' => $flagship->name,
                'address' => $flagship->address,
                'latitude' => $flagship->latitude,
                'longitude' => $flagship->longitude,
                'schedules' => $flagship->openDays()->map(fn ($s) => [
                    'day' => \App\Models\MarketSchedule::DAYS[$s->day_of_week],
                    'opening_time' => $s->opening_time?->format('g:i A'),
                    'closing_time' => $s->closing_time?->format('g:i A'),
                ])->values(),
            ] : null,
        ]);
    }

    public function markets(Request $request): JsonResponse
    {
        $day = $request->query('day');
        $page = max(1, (int) $request->query('page', '1'));

        $query = Market::query()
            ->with(['schedules', 'farmerMarkets.farmer.user'])
            ->withCount(['farmerMarkets as farmers_count' => fn ($q) => $q->where('status', 'active')])
            ->where('status', 'active')
            ->when($day !== null && $day !== '', fn ($q) => $q->whereHas('schedules', fn ($s) => $s
                ->where('day_of_week', (int) $day)->where('is_closed', false)))
            ->orderBy('name');

        $perPage = 9;
        $total = (clone $query)->toBase()->getCountForPagination();
        $markets = $query->forPage($page, $perPage)->get();

        [$favoriteMarketIds] = $this->favoriteSets($request, ['market']);

        return response()->json([
            'data' => $markets->map(fn ($m) => $this->marketCard($m)),
            'total' => $total, 'page' => $page, 'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / $perPage)),
            'days' => MarketSchedule::DAYS,
            'selectedDay' => $day,
            'favoriteMarketIds' => $favoriteMarketIds,
        ]);
    }

    public function marketShow(Market $market): JsonResponse
    {
        $market->load(['schedules', 'farmerMarkets.farmer.user', 'farmerMarkets.schedules']);

        $stalls = $market->farmerMarkets->where('status', 'active')->values();

        $featuredProducts = Product::query()
            ->where('status', 'active')
            ->whereHas('farmer', fn ($f) => $f->where('approval_status', 'approved'))
            ->whereHas('weeklyStocks', fn ($q) => $q
                ->whereIn('farmer_market_id', $stalls->pluck('id'))
                ->where('week_start', '>=', now()->startOfWeek(Carbon::SUNDAY)->toDateString())
                ->where('status', 'available')
                ->where('available_quantity', '>', 0))
            ->with(['farmer.user', 'category'])
            ->inRandomOrder()->take(8)->get();

        return response()->json([
            'market' => $this->marketCard($market),
            'stalls' => $stalls->map(fn ($s) => [
                'id' => $s->id,
                'stall_name' => $s->stall_name,
                'stall_location' => $s->stall_location,
                'farmer' => $this->farmerCard($s->farmer),
                'schedules' => $s->schedules->where('is_active', true)->values(),
            ]),
            'featuredProducts' => $featuredProducts->map(fn ($p) => $this->productCard($p)),
        ]);
    }

    public function farmers(Request $request): JsonResponse
    {
        $search = $request->query('q');
        $marketId = $request->query('market');
        $page = max(1, (int) $request->query('page', '1'));

        $query = FarmerProfile::query()
            ->with(['user', 'farmerMarkets.market'])
            ->where('approval_status', 'approved')
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('business_name', 'like', "%{$search}%")
                ->orWhere('contact_person', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))))
            ->when(is_numeric($marketId), fn ($q) => $q->whereHas('farmerMarkets', fn ($fm) => $fm
                ->where('market_id', (int) $marketId)->where('status', 'active')
                ->whereHas('market', fn ($m) => $m->where('status', 'active'))))
            ->orderBy('business_name');

        $perPage = 12;
        $total = (clone $query)->toBase()->getCountForPagination();
        $farmers = $query->forPage($page, $perPage)->get();

        [$favoriteFarmerIds, $favoriteProductIds] = $this->favoriteSets($request, ['farmer', 'product']);

        return response()->json([
            'data' => $farmers->map(fn ($f) => $this->farmerCard($f)),
            'total' => $total, 'page' => $page, 'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / $perPage)),
            'markets' => Market::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'search' => $search,
            'selectedMarket' => $marketId,
            'favoriteFarmerIds' => $favoriteFarmerIds,
            'favoriteProductIds' => $favoriteProductIds,
        ]);
    }

    public function farmerShow(FarmerProfile $farmer): JsonResponse
    {
        abort_unless($farmer->isApproved(), 404);

        $farmer->load(['user', 'farmerMarkets.market', 'farmerMarkets.schedules']);

        $stocks = WeeklyStock::sellable()
            ->whereHas('product', fn ($q) => $q->where('farmer_id', $farmer->id))
            ->with(['product.category', 'farmerMarket.market'])
            ->orderByDesc('week_start')
            ->get();

        [$favoriteFarmerIds, $favoriteProductIds] = $this->favoriteSets(request(), ['farmer', 'product']);

        return response()->json([
            'farmer' => $this->farmerCard($farmer),
            'stocks' => $stocks->map(fn ($s) => $this->stockCard($s)),
            'favoriteFarmerIds' => $favoriteFarmerIds,
            'favoriteProductIds' => $favoriteProductIds,
        ]);
    }

    // ---------------------------------------------------------------- helpers

    private function favoriteSets(Request $request, array $which): array
    {
        $out = array_fill(0, count($which), collect());

        if (! $request->user()?->isCustomer()) {
            return $out;
        }

        $customerId = $request->user()->customerProfile->id;

        foreach ($which as $i => $type) {
            $out[$i] = match ($type) {
                'product' => FavoriteProduct::where('customer_id', $customerId)->pluck('product_id'),
                'farmer' => FavoriteFarmer::where('customer_id', $customerId)->pluck('farmer_id'),
                'market' => FavoriteMarket::where('customer_id', $customerId)->pluck('market_id'),
                default => collect(),
            };
        }

        return $out;
    }

    /** Shape used by product/stock cards across public pages. */
    private function stockCard(WeeklyStock $s): array
    {
        return [
            'id' => $s->id,
            'week_start' => $s->week_start?->toDateString(),
            'unit' => $s->product->unit,
            'price' => (float) $s->product->price,
            'product' => array_merge(
                $s->product->only(['id', 'name', 'slug', 'image', 'price', 'unit']),
                ['category' => $s->product->category?->only(['id', 'name'])]
            ),
            'farmer' => $this->farmerCard($s->product->farmer),
            'farmerMarket' => [
                'id' => $s->farmerMarket->id,
                'stall_name' => $s->farmerMarket->stall_name,
                'market' => $s->farmerMarket->market?->only(['id', 'name', 'address']),
            ],
        ];
    }

    private function productCard(Product $p): array
    {
        return array_merge($p->only(['id', 'name', 'slug', 'image', 'price', 'unit']), [
            'category' => $p->category?->only(['id', 'name']),
            'farmer' => $p->farmer ? $this->farmerCard($p->farmer) : null,
        ]);
    }

    private function marketCard(Market $m): array
    {
        return array_merge($m->only(['id', 'name', 'address', 'image', 'status']), [
            'farmers_count' => (int) ($m->farmers_count ?? 0),
            'schedules' => $m->relationLoaded('schedules')
                ? $m->schedules->map(fn ($s) => $s->only(['id', 'day_of_week', 'opening_time', 'closing_time', 'is_closed']))
                : [],
        ]);
    }

    private function farmerCard(FarmerProfile $f): array
    {
        return array_merge($f->only([
            'id', 'business_name', 'contact_person', 'description', 'profile_image', 'approval_status',
        ]), [
            'name' => $f->user?->name,
            'avatar' => $f->relationLoaded('user') ? $f->user?->avatarUrl() : null,
            'markets' => $f->relationLoaded('farmerMarkets')
                ? $f->farmerMarkets->filter(fn ($fm) => $fm->status === 'active' && $fm->market?->status === 'active')
                    ->map(fn ($fm) => [
                        'id' => $fm->id,
                        'stall_name' => $fm->stall_name,
                        'market' => $fm->market?->only(['id', 'name', 'address', 'image']),
                        'schedules' => $fm->relationLoaded('schedules')
                            ? $fm->schedules->where('is_active', true)->values()
                                ->map(fn ($s) => $s->only(['id', 'day_of_week', 'start_time', 'end_time']))
                            : [],
                    ])->values()
                : [],
        ]);
    }
}

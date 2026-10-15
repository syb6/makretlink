<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\FarmerMarket;
use App\Models\FarmerProfile;
use App\Models\FarmerReview;
use App\Models\Market;
use App\Models\MarketSchedule;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PickupSlot;
use App\Models\Product;
use App\Models\StockTemplate;
use App\Models\WeeklyStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FarmerDataController extends Controller
{
    use BuildsApiPayloads;

    public function dashboard(): JsonResponse
    {
        $farmer = $this->currentFarmer();
        $farmerMarketIds = FarmerMarket::where('farmer_id', $farmer->id)->pluck('id');

        $base = Order::whereIn('farmer_market_id', $farmerMarketIds);

        return response()->json([
            'farmers' => array_merge($farmer->only(['id', 'business_name', 'approval_status']), [
                'name' => Auth::user()->name,
            ]),
            'total_orders' => (clone $base)->count(),
            'pending_orders' => (clone $base)->where('status', 'placed')->count(),
            'revenue' => (float) (clone $base)->whereIn('status', ['accepted', 'ready_for_pickup', 'completed'])->sum('total_amount'),
            'product_count' => $farmer->products()->count(),
            'best_sellers' => OrderItem::select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_sales'))
                ->whereIn('order_id', (clone $base)->select('id'))
                ->groupBy('product_name')
                ->orderByDesc('total_qty')
                ->take(5)
                ->get(),
            'recent_orders' => (clone $base)->with('customer.user')->latest('placed_at')->take(5)->get()
                ->map(fn ($o) => $this->orderRow($o)),
        ]);
    }

    public function profile(): JsonResponse
    {
        $farmer = $this->currentFarmer()->load('user');

        return response()->json([
            'farmer' => array_merge($farmer->only([
                'id', 'business_name', 'contact_person', 'address', 'description', 'profile_image', 'approval_status',
            ]), [
                'user' => $farmer->user?->only(['name', 'email', 'phone']),
                'avatar' => $farmer->user?->avatarUrl(),
            ]),
        ]);
    }

    public function stalls(): JsonResponse
    {
        $farmer = $this->currentFarmer();

        $stalls = FarmerMarket::with(['market', 'schedules'])
            ->where('farmer_id', $farmer->id)
            ->get();
        $availableMarkets = Market::where('status', 'active')
            ->whereNotIn('id', $stalls->pluck('market_id'))
            ->orderBy('name')->get(['id', 'name']);
        $openDays = MarketSchedule::whereIn('market_id', Market::where('status', 'active')->pluck('id'))
            ->where('is_closed', false)
            ->orderBy('day_of_week')->get();

        return response()->json([
            'stalls' => $stalls->map(fn ($s) => [
                'id' => $s->id,
                'stall_name' => $s->stall_name,
                'stall_location' => $s->stall_location,
                'status' => $s->status,
                'market' => $s->market?->only(['id', 'name', 'address']),
                'schedules' => $s->schedules->map(fn ($x) => $x->only(['id', 'day_of_week', 'start_time', 'end_time', 'is_active'])),
            ]),
            'available_markets' => $availableMarkets,
            'open_days' => $openDays,
        ]);
    }

    public function products(): JsonResponse
    {
        $products = Product::with('category')
            ->where('farmer_id', $this->currentFarmer()->id)
            ->withTrashed()
            ->latest();

        $paginated = $this->paginate($products, request(), 10);

        return response()->json(array_merge($paginated, [
            'rows' => $paginated['rows']->map(fn ($p) => array_merge($p->only([
                'id', 'name', 'slug', 'description', 'price', 'unit', 'image', 'status', 'deleted_at',
            ]), [
                'category' => $p->category?->only(['id', 'name']),
            ])),
            'categories' => Category::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'stalls' => FarmerMarket::with('market')
                ->where('farmer_id', $this->currentFarmer()->id)
                ->where('status', 'active')->orderBy('id')->get()
                ->map(fn ($s) => array_merge($s->only(['id', 'stall_name']), [
                    'market' => $s->market?->only(['id', 'name']),
                ])),
        ]));
    }

    public function stock(Request $request): JsonResponse
    {
        $week = $request->query('week');
        try {
            $weekStart = $week
                ? Carbon::parse($week)->startOfWeek(Carbon::SUNDAY)
                : now()->startOfWeek(Carbon::SUNDAY);
        } catch (\Throwable) {
            $weekStart = now()->startOfWeek(Carbon::SUNDAY);
        }

        $farmer = $this->currentFarmer();
        $myFarmerMarketIds = FarmerMarket::where('farmer_id', $farmer->id)->pluck('id')->all();

        $stocks = WeeklyStock::with(['product.category', 'farmerMarket.market'])
            ->whereIn('farmer_market_id', $myFarmerMarketIds)
            ->where('week_start', $weekStart->toDateString())
            ->orderBy('product_id')->get();

        return response()->json([
            'week_start' => $weekStart->toDateString(),
            'prev_week' => $weekStart->copy()->subWeek()->toDateString(),
            'next_week' => $weekStart->copy()->addWeek()->toDateString(),
            'stocks' => $stocks->map(fn ($s) => array_merge($this->stockCard($s), [
                'product' => array_merge($s->product->only(['id', 'name', 'image', 'price', 'unit']), [
                    'category' => $s->product->category?->only(['id', 'name']),
                ]),
            ])),
            'farmer_markets' => FarmerMarket::with('market')->where('farmer_id', $farmer->id)->get()
                ->map(fn ($s) => array_merge($s->only(['id', 'stall_name']), [
                    'market' => $s->market?->only(['id', 'name']),
                ])),
            'products' => $farmer->products()->where('status', 'active')->orderBy('name')->get()
                ->map(fn ($p) => $p->only(['id', 'name', 'unit', 'price'])),
            'templates' => StockTemplate::where('is_active', true)
                ->whereIn('farmer_market_id', $myFarmerMarketIds)->get()
                ->map(fn ($t) => $t->only(['id', 'product_id', 'farmer_market_id', 'default_quantity'])),
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $ids = FarmerMarket::where('farmer_id', $this->currentFarmer()->id)->pluck('id');

        $baseQuery = Order::whereIn('farmer_market_id', $ids)
            ->with(['items', 'customer.user', 'pickupSlot'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('placed_at');

        $paginated = $this->paginate($baseQuery, $request, 15);

        $paginated['rows'] = $paginated['rows']->map(fn ($o) => $this->orderRow($o));

        return response()->json(array_merge($paginated, ['statuses' => Order::STATUSES]));
    }

    public function orderShow(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($order);

        $order->load(['items.product', 'customer.user', 'pickupSlot', 'statusHistories.changedBy']);

        $items = $order->items->map(fn ($i) => array_merge($i->only([
            'id', 'product_id', 'product_name', 'unit', 'quantity', 'unit_price', 'subtotal',
        ]), ['product' => $i->product?->only(['id', 'name', 'image'])]));

        return response()->json([
            'order' => array_merge($this->orderRow($order), [
                'items' => $items,
                'status_histories' => $order->statusHistories->map(fn ($h) => [
                    'id' => $h->id, 'status' => $h->status, 'note' => $h->note,
                    'changed_at' => $h->changed_at?->toISOString(),
                    'changed_by' => $h->changedBy?->name,
                ]),
            ]),
        ]);
    }

    public function reviews(): JsonResponse
    {
        $reviews = FarmerReview::with(['customer.user', 'order'])
            ->where('farmer_id', $this->currentFarmer()->id)
            ->latest();

        $paginated = $this->paginate($reviews, request(), 10);

        $paginated['rows'] = $paginated['rows']->map(fn ($r) => array_merge($r->only([
            'id', 'rating', 'comment', 'farmer_reply', 'replied_at', 'status', 'created_at',
        ]), [
            'customer' => $r->customer?->user?->only(['name']),
            'order' => $r->order?->only(['id', 'order_number']),
        ]));

        return response()->json($paginated);
    }

    public function slots(): JsonResponse
    {
        $ids = FarmerMarket::where('farmer_id', $this->currentFarmer()->id)->pluck('id')->all();

        $slots = PickupSlot::with('farmerMarket.market')
            ->whereIn('farmer_market_id', $ids)
            ->orderBy('pickup_date')->orderBy('start_time')->get();

        $grouped = $slots->groupBy(fn ($s) => Carbon::parse($s->pickup_date)->toDateString())
            ->map(fn ($g) => $g->map(fn ($s) => array_merge($s->only([
                'id', 'farmer_market_id', 'pickup_date', 'start_time', 'end_time', 'cutoff_at', 'max_orders', 'status',
            ]), ['farmer_market' => $s->farmerMarket ? array_merge($s->farmerMarket->only(['stall_name']), [
                'market' => $s->farmerMarket->market?->only(['id', 'name']),
            ]) : null, 'order_count' => $s->orders()->count()]))->values());

        return response()->json([
            'grouped' => $grouped,
            'farmer_markets' => FarmerMarket::with('market')->where('farmer_id', $this->currentFarmer()->id)->get()
                ->map(fn ($s) => array_merge($s->only(['id', 'stall_name']), [
                    'market' => $s->market?->only(['id', 'name']),
                ])),
        ]);
    }

    // ---------------------------------------------------------------- helpers

    private function currentFarmer(): FarmerProfile
    {
        $farmer = FarmerProfile::withTrashed()->firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'business_name' => Auth::user()->name."'s Stall",
                'contact_person' => Auth::user()->name,
                'address' => '',
                'approval_status' => 'pending',
            ]
        );

        if ($farmer->trashed()) {
            $farmer->restore();
        }

        return $farmer;
    }

    private function orderRow(Order $o): array
    {
        $out = $o->only([
            'id', 'order_number', 'status', 'total_amount', 'placed_at',
            'farmer_name', 'market_name', 'pickup_date', 'pickup_start_time', 'pickup_end_time',
        ]);

        $out['items_count'] = $o->relationLoaded('items') ? $o->items->count() : null;
        $out['items'] = $o->relationLoaded('items')
            ? $o->items->map(fn ($i) => $i->only(['id', 'product_name', 'unit', 'quantity', 'unit_price', 'subtotal']))
            : [];
        $out['customer'] = $o->relationLoaded('customer') && $o->customer
            ? array_merge($o->customer->only(['id']), ['user' => $o->customer->user?->only(['name', 'email', 'phone'])])
            : null;
        $out['slot'] = $o->relationLoaded('pickupSlot') && $o->pickupSlot
            ? $o->pickupSlot->only(['id', 'pickup_date', 'start_time', 'end_time', 'cutoff_at'])
            : null;

        return $out;
    }

    private function authorizeOrder(Order $order): void
    {
        $mine = FarmerMarket::where('farmer_id', $this->currentFarmer()->id)->pluck('id');

        abort_unless($order->farmer_market_id && $mine->contains($order->farmer_market_id), 403);
    }
}

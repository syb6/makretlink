<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Cart;
use App\Models\CustomerProfile;
use App\Models\FavoriteFarmer;
use App\Models\FavoriteMarket;
use App\Models\FavoriteProduct;
use App\Models\FarmerReview;
use App\Models\Order;
use App\Models\PickupSlot;
use App\Models\ProductReview;
use App\Models\WeeklyStock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerDataController extends Controller
{
    use BuildsApiPayloads;

    public function dashboard(): JsonResponse
    {
        $customerId = $this->customerId();

        $baseQuery = Order::where('customer_id', $customerId);

        return response()->json([
            'total_orders' => (clone $baseQuery)->count(),
            'active_orders' => (clone $baseQuery)->whereIn('status', ['placed', 'accepted', 'ready_for_pickup'])->count(),
            'completed_orders' => (clone $baseQuery)->where('status', 'completed')->count(),
            'favorite_count' => FavoriteProduct::where('customer_id', $customerId)->count(),
            'spent' => (float) (clone $baseQuery)->where('status', 'completed')->sum('total_amount'),
            'recent_orders' => (clone $baseQuery)->with(['farmerMarket.market', 'items'])
                ->latest('placed_at')->take(5)->get()
                ->map(fn ($o) => $this->orderSummary($o)),
        ]);
    }

    public function profile(): JsonResponse
    {
        $user = Auth::user()->load('customerProfile');

        return response()->json([
            'user' => array_merge($user->only(['id', 'name', 'email', 'phone']), [
                'avatar' => $user->avatarUrl(),
            ]),
            'profile' => Auth::user()->customerProfile?->only(['id', 'address', 'profile_image']),
        ]);
    }

    public function cart(): JsonResponse
    {
        $cart = Cart::with(['items.product.farmer.user', 'items.farmerMarket.market'])
            ->where('customer_id', $this->customerId())
            ->first();

        $items = collect();

        if ($cart) {
            $items = $cart->items->map(function ($item) {
                return array_merge($item->only(['id', 'product_id', 'farmer_market_id', 'quantity', 'price']), [
                    'product' => $item->product ? array_merge(
                        $item->product->only(['id', 'name', 'image', 'price', 'unit']),
                        ['farmer' => ['business_name' => $item->product->farmer?->business_name]]
                    ) : null,
                    'farmerMarket' => [
                        'id' => $item->farmer_market_id,
                        'stall_name' => $item->farmerMarket?->stall_name,
                        'market' => $item->farmerMarket?->market?->only(['id', 'name']),
                    ],
                ]);
            });
        }

        return response()->json([
            'items' => $items,
            'count' => $items->count(),
            'total' => (float) $items->sum(fn ($i) => $i['quantity'] * $i['price']),
        ]);
    }

    public function checkout(): JsonResponse
    {
        $cart = Cart::with(['items.product', 'items.farmerMarket.market'])
            ->where('customer_id', $this->customerId())
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['empty' => true]);
        }

        $cart->items->each(function ($item) {
            $item->is_unsellable = ! WeeklyStock::sellable()
                ->where('product_id', $item->product_id)
                ->where('farmer_market_id', $item->farmer_market_id)
                ->exists();
        });

        $orderable = $cart->items->reject(fn ($i) => $i->is_unsellable);

        $groups = $orderable->groupBy('farmer_market_id')->map(function ($items, $farmerMarketId) {
            $fm = $items->first()->farmerMarket;

            $slots = PickupSlot::where('farmer_market_id', $farmerMarketId)
                ->where('status', 'active')
                ->where('cutoff_at', '>', now())
                ->orderBy('pickup_date')
                ->orderBy('start_time')
                ->get()
                ->map(fn ($s) => $s->only(['id', 'pickup_date', 'start_time', 'end_time', 'cutoff_at', 'max_orders', 'status']))
                ->values();

            return [
                'farmer_market_id' => (int) $farmerMarketId,
                'farmer' => $fm?->farmer?->only(['business_name']),
                'market' => $fm?->market?->only(['id', 'name']),
                'items' => $items->map(fn ($i) => array_merge($i->only(['id', 'quantity', 'price']), [
                    'product' => $i->product?->only(['id', 'name', 'image', 'unit']),
                ])),
                'slots' => $slots,
            ];
        })->values();

        return response()->json([
            'empty' => false,
            'groups' => $groups,
            'unsellable' => $cart->items->filter(fn ($i) => $i->is_unsellable)->map(fn ($i) => [
                'id' => $i->id,
                'product' => $i->product?->only(['id', 'name', 'unit']),
            ]),
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $baseQuery = Order::with(['items.product', 'pickupSlot', 'farmerMarket.farmer.user'])
            ->withExists(['productReviews', 'farmerReviews'])
            ->where('customer_id', $this->customerId())
            ->latest('placed_at');

        $paginated = $this->paginate($baseQuery, $request, 10);

        $paginated['rows'] = $paginated['rows']
            ->map(fn ($o) => $this->orderSummary($o, withReviews: true));

        return response()->json($paginated);
    }

    public function orderShow(Order $order): JsonResponse
    {
        abort_unless($order->customer_id === $this->customerId(), 403);

        $order->load(['items.product', 'pickupSlot', 'farmerMarket.market', 'farmerMarket.farmer.user', 'statusHistories.changedBy']);

        $existingProductReview = ProductReview::where('order_id', $order->id)
            ->where('customer_id', $this->customerId())->get()->keyBy('product_id');
        $existingFarmerReview = FarmerReview::where('order_id', $order->id)
            ->where('customer_id', $this->customerId())->first();

        return response()->json([
            'order' => array_merge($this->orderSummary($order, detail: true), [
                'status_histories' => $order->statusHistories->map(fn ($h) => [
                    'id' => $h->id, 'status' => $h->status, 'note' => $h->note,
                    'changed_at' => $h->changed_at?->toISOString(),
                    'changed_by' => $h->changedBy?->name,
                ]),
            ]),
            'existing_product_reviews' => $existingProductReview->map(fn ($r) => $r->only(['product_id', 'rating', 'comment'])),
            'existing_farmer_review' => $existingFarmerReview?->only(['rating', 'comment']),
        ]);
    }

    public function favorites(): JsonResponse
    {
        $customerId = $this->customerId();

        $products = FavoriteProduct::with(['product.farmer.user', 'product.category'])
            ->where('customer_id', $customerId)->get();
        $farmers = FavoriteFarmer::with('farmer.user')->where('customer_id', $customerId)->get();
        $markets = FavoriteMarket::with('market')->where('customer_id', $customerId)->get();

        return response()->json([
            'products' => $products->map(fn ($f) => $f->product ? $this->productCard($f->product) : null)
                ->filter()->values(),
            'farmers' => $farmers->map(fn ($f) => $f->farmer ? $this->farmerCard($f->farmer) : null)
                ->filter()->values(),
            'markets' => $markets->map(fn ($f) => $f->market ? $this->marketCard($f->market) : null)
                ->filter()->values(),
        ]);
    }

    // ---------------------------------------------------------------- helpers

    private function customerId(): int
    {
        return CustomerProfile::withTrashed()->firstOrCreate(
            ['user_id' => Auth::id()],
            ['address' => '']
        )->id;
    }

    private function orderSummary(Order $o, bool $withReviews = false, bool $detail = false): array
    {
        $out = $o->only([
            'id', 'order_number', 'status', 'total_amount', 'customer_note',
            'placed_at', 'accepted_at', 'ready_at', 'completed_at', 'cancelled_at',
            'farmer_name', 'market_name', 'pickup_date', 'pickup_start_time', 'pickup_end_time',
        ]);

        $out['items'] = $o->relationLoaded('items')
            ? $o->items->map(fn ($i) => array_merge($i->only([
                'id', 'product_id', 'product_name', 'unit', 'quantity', 'unit_price', 'subtotal',
            ]), ['product' => $i->relationLoaded('product') && $i->product
                ? $i->product->only(['id', 'name', 'image']) : null]))
            : [];
        $out['market'] = $o->relationLoaded('farmerMarket') && $o->farmerMarket
            ? $o->farmerMarket->market?->only(['id', 'name'])
            : ($o->market_name ?: null);
        $out['slot'] = $o->relationLoaded('pickupSlot') && $o->pickupSlot
            ? $o->pickupSlot->only(['id', 'pickup_date', 'start_time', 'end_time', 'cutoff_at'])
            : null;
        $out['customer'] = $o->relationLoaded('farmerMarket') && $o->farmerMarket?->relationLoaded('farmer')
            ? $o->farmerMarket->farmer?->user?->only(['name', 'email', 'phone'])
            : null;

        if ($withReviews) {
            $out['has_product_reviews'] = (bool) ($o->product_reviews_exists ?? false);
            $out['has_farmer_review'] = (bool) ($o->farmer_reviews_exists ?? false);
        }

        if ($detail) {
            $out['farmer_market'] = $o->farmerMarket?->only(['id', 'market_id', 'stall_name']);
        }

        return $out;
    }
}

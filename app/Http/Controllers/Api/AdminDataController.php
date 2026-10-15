<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\FarmerProfile;
use App\Models\FarmerReview;
use App\Models\FeedbackMessage;
use App\Models\Market;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDataController extends Controller
{
    use BuildsApiPayloads;

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'total_farmers' => User::where('role', 'farmer')->count(),
            'pending_farmers' => FarmerProfile::where('approval_status', 'pending')->count(),
            'total_customers' => User::where('role', 'customer')->count(),
            'active_customers' => User::where('role', 'customer')->where('status', 'active')->count(),
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'placed')->count(),
            'revenue' => (float) Order::whereIn('status', ['accepted', 'ready_for_pickup', 'completed'])->sum('total_amount'),
            'total_products' => Product::count(),
            'recent_orders' => Order::with('customer.user')->latest('placed_at')->take(6)->get()
                ->map(fn ($o) => $this->orderSummary($o)),
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $role = $request->query('role');
        $status = $request->query('status');
        $search = $request->query('q');
        $approval = $request->query('approval');

        $query = User::query()
            ->with(['farmerProfile', 'customerProfile'])
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($approval, fn ($q) => $q->whereHas('farmerProfile', fn ($fp) => $fp->where('approval_status', $approval)))
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name');

        $paginated = $this->paginate($query, $request, 15);

        $paginated['rows'] = $paginated['rows']->map(fn ($u) => array_merge($u->only([
            'id', 'name', 'email', 'phone', 'role', 'status',
        ]), [
            'avatar' => $u->avatarUrl(),
            'farmer_profile' => $u->farmerProfile?->only(['id', 'business_name', 'approval_status']),
            'customer_profile' => $u->customerProfile?->only(['id', 'address']),
        ]));

        return response()->json(array_merge($paginated, [
            'filters' => ['role' => $role, 'status' => $status, 'approval' => $approval, 'q' => $search],
        ]));
    }

    public function markets(): JsonResponse
    {
        $paginated = $this->paginate(
            Market::query()->with('schedules')->withCount('farmerMarkets')->orderBy('name'),
            request(),
            10
        );

        $paginated['rows'] = $paginated['rows']->map(fn ($m) => array_merge($this->marketCard($m), [
            'farmer_markets_count' => $m->farmer_markets_count,
        ]));

        return response()->json($paginated);
    }

    public function categories(): JsonResponse
    {
        $paginated = $this->paginate(
            Category::query()->withCount('products')->orderBy('name'),
            request(),
            10
        );

        $paginated['rows'] = $paginated['rows']->map(fn ($c) => array_merge($c->only([
            'id', 'name', 'slug', 'image', 'status',
        ]), ['products_count' => $c->products_count]));

        return response()->json($paginated);
    }

    public function feedback(): JsonResponse
    {
        $paginated = $this->paginate(
            FeedbackMessage::query()->orderByRaw('read_at IS NULL DESC')->latest(),
            request(),
            15
        );

        $paginated['rows'] = $paginated['rows']->map(fn ($m) => $m->only([
            'id', 'name', 'email', 'message', 'read_at', 'created_at',
        ]));

        $paginated['unread_count'] = FeedbackMessage::whereNull('read_at')->count();

        return response()->json($paginated);
    }

    public function moderation(): JsonResponse
    {
        return response()->json([
            'products' => Product::with(['farmer.user', 'category'])->latest()->take(20)->get()
                ->map(fn ($p) => $this->productCard($p)),
            'product_reviews' => ProductReview::with(['product', 'customer.user'])->latest()->take(20)->get()
                ->map(fn ($r) => array_merge($r->only(['id', 'rating', 'comment', 'status']), [
                    'product' => $r->product?->only(['id', 'name', 'image']),
                    'customer' => $r->customer?->user?->only(['name']),
                ])),
            'farmer_reviews' => FarmerReview::with(['farmer.user', 'customer.user'])->latest()->take(20)->get()
                ->map(fn ($r) => array_merge($r->only(['id', 'rating', 'comment', 'farmer_reply', 'status']), [
                    'farmer' => $r->farmer?->only(['id', 'business_name']) + ['user' => $r->farmer?->user?->only(['name'])],
                    'customer' => $r->customer?->user?->only(['name']),
                ])),
        ]);
    }

    public function reports(): JsonResponse
    {
        $ordersByStatus = Order::select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('status')->get();
        $revenueByMarket = Order::query()
            ->join('farmer_markets', 'orders.farmer_market_id', '=', 'farmer_markets.id')
            ->join('markets', 'farmer_markets.market_id', '=', 'markets.id')
            ->select('markets.name', DB::raw('COUNT(*) as orders'), DB::raw('SUM(orders.total_amount) as revenue'))
            ->whereIn('orders.status', ['accepted', 'ready_for_pickup', 'completed'])
            ->groupBy('markets.id', 'markets.name')
            ->orderByDesc('revenue')->get();
        $mostActiveFarmers = Order::query()
            ->join('farmer_markets', 'orders.farmer_market_id', '=', 'farmer_markets.id')
            ->join('farmer_profiles', 'farmer_markets.farmer_id', '=', 'farmer_profiles.id')
            ->select('farmer_profiles.business_name', DB::raw('COUNT(*) as orders'), DB::raw('SUM(orders.total_amount) as revenue'))
            ->whereIn('orders.status', ['accepted', 'ready_for_pickup', 'completed'])
            ->groupBy('farmer_profiles.id', 'farmer_profiles.business_name')
            ->orderByDesc('orders')->take(10)->get();

        return response()->json([
            'orders_by_status' => $ordersByStatus,
            'revenue_by_market' => $revenueByMarket,
            'most_active_farmers' => $mostActiveFarmers,
            'total_orders' => Order::count(),
            'total_revenue' => (float) Order::whereIn('status', ['accepted', 'ready_for_pickup', 'completed'])->sum('total_amount'),
        ]);
    }

    public function announcements(): JsonResponse
    {
        $paginated = $this->paginate(Announcement::query()->with('author')->latest(), request(), 10);

        $paginated['rows'] = $paginated['rows']->map(fn ($a) => array_merge($a->only([
            'id', 'title', 'message', 'status', 'published_at', 'created_at',
        ]), ['author' => $a->author?->only(['id', 'name'])]));

        return response()->json($paginated);
    }

    public function profile(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'user' => array_merge($user->only(['id', 'name', 'email', 'phone', 'profile_photo_path']), [
                'avatar' => $user->avatarUrl(),
            ]),
        ]);
    }

    // ---------------------------------------------------------------- helpers

    private function orderSummary(Order $o): array
    {
        $out = $o->only(['id', 'order_number', 'status', 'total_amount', 'placed_at',
            'farmer_name', 'market_name', 'pickup_date', 'pickup_start_time', 'pickup_end_time']);
        $out['customer'] = $o->relationLoaded('customer') && $o->customer
            ? array_merge($o->customer->only(['id']), ['user' => $o->customer->user?->only(['name', 'email'])])
            : null;
        $out['items_count'] = $o->items()->count();

        return $out;
    }
}

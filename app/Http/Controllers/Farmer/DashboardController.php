<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\FarmerMarket;
use App\Models\FarmerReview;
use App\Models\Market;
use App\Models\MarketSchedule;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ProfileImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $farmer = Auth::user()->farmerProfile;
        $farmerMarketIds = FarmerMarket::where('farmer_id', $farmer->id)->pluck('id');

        $base = Order::whereIn('farmer_market_id', $farmerMarketIds);

        return view('farmer.dashboard', [
            'farmer' => $farmer,
            'totalOrders' => (clone $base)->count(),
            'pendingOrders' => (clone $base)->where('status', 'placed')->count(),
            'revenue' => (float) (clone $base)->whereIn('status', ['accepted', 'ready_for_pickup', 'completed'])->sum('total_amount'),
            'productCount' => $farmer->products()->count(),
            'bestSellers' => OrderItem::select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_sales'))
                ->whereIn('order_id', (clone $base)->select('id'))
                ->groupBy('product_name')
                ->orderByDesc('total_qty')
                ->take(5)
                ->get(),
            'recentOrders' => (clone $base)->with('customer.user')->latest('placed_at')->take(5)->get(),
        ]);
    }

    public function editProfile()
    {
        return view('farmer.profile', ['farmer' => Auth::user()->farmerProfile->load('user')]);
    }

    public function stalls()
    {
        $farmer = Auth::user()->farmerProfile->load('user');

        $stalls = FarmerMarket::with(['market', 'schedules'])
            ->where('farmer_id', $farmer->id)
            ->get();

        $joinedMarketIds = $stalls->pluck('market_id');

        $availableMarkets = Market::where('status', 'active')
            ->whereNotIn('id', $joinedMarketIds)
            ->orderBy('name')
            ->get();

        // Days each active market is open (used to enable/disable the day checkboxes)
        $openDays = MarketSchedule::whereIn('market_id', Market::where('status', 'active')->pluck('id'))
            ->where('is_closed', false)
            ->orderBy('day_of_week')
            ->get();

        return view('farmer.stalls', [
            'farmer' => $farmer,
            'stalls' => $stalls,
            'availableMarkets' => $availableMarkets,
            'openDays' => $openDays,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $farmer = Auth::user()->farmerProfile;

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:150'],
            'contact_person' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64'],
        ]);

        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = app(ProfileImageService::class)->replace(
                $request->file('profile_image'),
                $farmer->profile_image,
                'farmer-'.$farmer->id
            );
        }

        $farmer->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function replyReview(Request $request, FarmerReview $review)
    {
        abort_unless($review->farmer_id === Auth::user()->farmerProfile->id, 403);

        $data = $request->validate(['farmer_reply' => ['required', 'string', 'max:1000']]);

        $review->update([
            'farmer_reply' => $data['farmer_reply'],
            'replied_at' => now(),
        ]);

        return back()->with('success', 'Reply posted.');
    }

    /**
     * SRS: farmer profile includes "operating markets, operating days, pickup
     * time windows". Stalls must be created/managed by the farmer himself.
     */
    public function storeStall(Request $request)
    {
        $data = $request->validate([
            'market_id' => ['required', 'exists:markets,id'],
            'stall_name' => ['nullable', 'string', 'max:150'],
            'stall_location' => ['nullable', 'string', 'max:255'],
            'days' => ['nullable', 'array'],
            'days.*' => ['integer', 'between:0,6'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
        ]);

        $farmer = Auth::user()->farmerProfile;

        abort_unless($farmer->isApproved(), 403, 'Your stall must be approved by an administrator first.');

        abort_unless(Market::where('id', $data['market_id'])->where('status', 'active')->exists(), 422, 'That market is not active.');

        $stall = FarmerMarket::updateOrCreate(
            ['farmer_id' => $farmer->id, 'market_id' => $data['market_id']],
            [
                'stall_name' => $data['stall_name'] ?? $farmer->business_name,
                'stall_location' => $data['stall_location'] ?? null,
                'status' => 'active',
            ]
        );

        // Weekly presence (only days the market actually operates)
        $days = collect($data['days'] ?? [])
            ->filter(fn ($d) => MarketSchedule::where('market_id', $data['market_id'])
                ->where('day_of_week', $d)
                ->where('is_closed', false)
                ->exists());

        foreach ($days as $day) {
            $stall->schedules()->updateOrCreate(
                ['day_of_week' => $day],
                [
                    'start_time' => $data['start_time'] ?? '08:00',
                    'end_time' => $data['end_time'] ?? '14:00',
                    'is_active' => true,
                ]
            );
        }

        return back()->with('success', 'Stall saved — you are now listed at '.$stall->market->name.'.');
    }

    public function toggleStall(FarmerMarket $stall)
    {
        abort_unless($stall->farmer_id === Auth::user()->farmerProfile->id, 403);

        $stall->update(['status' => $stall->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', 'Stall '.($stall->status === 'active' ? 'activated' : 'paused').'.');
    }

    public function updateStallSchedule(Request $request, FarmerMarket $stall)
    {
        abort_unless($stall->farmer_id === Auth::user()->farmerProfile->id, 403);

        $data = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
        ]);

        $schedule = $stall->schedules()->where('day_of_week', $data['day_of_week'])->first();

        // Sending both times empty removes the day from the schedule.
        if (empty($data['start_time']) && empty($data['end_time'])) {
            $schedule?->delete();

            return back()->with('success', 'Day removed from your schedule.');
        }

        $stall->schedules()->updateOrCreate(
            ['day_of_week' => $data['day_of_week']],
            [
                'start_time' => $data['start_time'] ?? '08:00',
                'end_time' => $data['end_time'] ?? '14:00',
                'is_active' => true,
            ]
        );

        return back()->with('success', 'Schedule updated.');
    }
}

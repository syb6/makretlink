<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $ordersByStatus = Order::select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('status')
            ->get();

        $revenueByMarket = Order::query()
            ->join('farmer_markets', 'orders.farmer_market_id', '=', 'farmer_markets.id')
            ->join('markets', 'farmer_markets.market_id', '=', 'markets.id')
            ->select('markets.name', DB::raw('COUNT(*) as orders'), DB::raw('SUM(orders.total_amount) as revenue'))
            ->whereIn('orders.status', ['accepted', 'ready_for_pickup', 'completed'])
            ->groupBy('markets.id', 'markets.name')
            ->orderByDesc('revenue')
            ->get();

        $mostActiveFarmers = Order::query()
            ->join('farmer_markets', 'orders.farmer_market_id', '=', 'farmer_markets.id')
            ->join('farmer_profiles', 'farmer_markets.farmer_id', '=', 'farmer_profiles.id')
            ->select('farmer_profiles.business_name', DB::raw('COUNT(*) as orders'), DB::raw('SUM(orders.total_amount) as revenue'))
            ->whereIn('orders.status', ['accepted', 'ready_for_pickup', 'completed'])
            ->groupBy('farmer_profiles.id', 'farmer_profiles.business_name')
            ->orderByDesc('orders')
            ->take(10)
            ->get();

        return view('admin.reports', [
            'ordersByStatus' => $ordersByStatus,
            'revenueByMarket' => $revenueByMarket,
            'mostActiveFarmers' => $mostActiveFarmers,
            'totalOrders' => Order::count(),
            'totalRevenue' => (float) Order::whereIn('status', ['accepted', 'ready_for_pickup', 'completed'])->sum('total_amount'),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmerProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'totalFarmers' => User::where('role', 'farmer')->count(),
            'pendingFarmers' => FarmerProfile::where('approval_status', 'pending')->count(),
            'totalCustomers' => User::where('role', 'customer')->count(),
            'activeCustomers' => User::where('role', 'customer')->where('status', 'active')->count(),
            'totalOrders' => Order::count(),
            'pendingOrders' => Order::where('status', 'placed')->count(),
            'revenue' => (float) Order::whereIn('status', ['accepted', 'ready_for_pickup', 'completed'])->sum('total_amount'),
            'totalProducts' => Product::count(),
            'recentOrders' => Order::with('customer.user')->latest('placed_at')->take(6)->get(),
        ]);
    }
}

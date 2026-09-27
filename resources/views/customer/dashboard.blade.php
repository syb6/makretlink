@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <div class="mb-4">
            <span class="badge-sub">CUSTOMER MARKET PORTAL</span>
            <h1 class="h2 mb-1">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}!</h1>
            <p class="text-muted mb-0">Here's what's happening with your pre-orders.</p>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-bag"></i></div>
                    <div><div class="stat-label">Total Orders</div><div class="stat-number">{{ $totalOrders }}</div></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
                    <div><div class="stat-label">Pending / Ready</div><div class="stat-number">{{ $activeOrders }}</div></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
                    <div><div class="stat-label">Completed</div><div class="stat-number">{{ $completedOrders }}</div></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-heart"></i></div>
                    <div><div class="stat-label">Saved Favorites</div><div class="stat-number">{{ $favoriteCount }}</div></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="dashboard-card h-100">
                    <div class="dashboard-card-header">
                        <h3 class="h5 mb-0">Recent Orders</h3>
                        <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-ml">View All</a>
                    </div>
                    <div class="dashboard-card-body">
                        @forelse ($recentOrders as $order)
                            <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-3 flex-wrap gap-2">
                                <div>
                                    <a href="{{ route('orders.show', $order) }}" class="fw-bold text-decoration-none">{{ $order->order_number }}</a>
                                    <div class="small text-muted">{{ $order->farmer_name }} &middot; {{ $order->market_name }} &middot; Pickup {{ $order->pickup_date?->format('M j') }}</div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <strong>Rs {{ number_format($order->total_amount, 2) }}</strong>
                                    <span class="status-pill status-{{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span>
                                    <a href="{{ route('orders.show', $order) }}" class="small text-decoration-none">Details <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        @empty
                            <div class="empty">
                                <i class="bi bi-bag-x"></i>
                                <strong>No orders yet.</strong>
                                <p class="small mb-2">Reserve your first basket of fresh produce.</p>
                                <a href="{{ route('products.index') }}" class="btn btn-sm btn-ml">Shop Produce</a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="dashboard-card h-100">
                    <div class="dashboard-card-header"><h3 class="h5 mb-0">Quick Actions</h3></div>
                    <div class="dashboard-card-body d-flex flex-column gap-2">
                        <a href="{{ route('markets.index') }}" class="btn-marketlink-outline btn btn-sm justify-content-start"><i class="bi bi-shop text-success me-2"></i> Find a Market</a>
                        <a href="{{ route('products.index') }}" class="btn-marketlink-outline btn btn-sm justify-content-start"><i class="bi bi-bag text-success me-2"></i> Shop Produce</a>
                        <a href="{{ route('cart.index') }}" class="btn-marketlink-outline btn btn-sm justify-content-start"><i class="bi bi-basket text-success me-2"></i> Pre-Order Basket</a>
                        <a href="{{ route('favorites.index') }}" class="btn-marketlink-outline btn btn-sm justify-content-start"><i class="bi bi-heart text-danger me-2"></i> My Favorites</a>
                        <a href="{{ route('profile.edit') }}" class="btn-marketlink-outline btn btn-sm justify-content-start"><i class="bi bi-person-gear text-success me-2"></i> Profile Settings</a>
                        <hr class="my-1">
                        <div class="text-center py-3" style="background: var(--primary-light); border-radius: 8px;">
                            <div class="small text-muted mb-1">Lifetime spend (paid at pickup)</div>
                            <div class="fs-4 fw-bold" style="color: var(--primary-contrast)">Rs {{ number_format($spent, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

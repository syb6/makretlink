@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
<section class="py-5">
    <div class="container">
        <h1 class="section-title mb-4">Hi, {{ explode(' ', auth()->user()->name)[0] }} 👋</h1>

        <div class="row g-4 mb-4">
            <div class="col-md-3 col-6">
                <div class="card stat-card stat-green p-4">
                    <i class="bi bi-bag-check stat-icon"></i>
                    <div class="fs-3 fw-bold">{{ $totalOrders }}</div>
                    <div class="small opacity-75">Total orders</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card stat-amber p-4">
                    <i class="bi bi-hourglass-split stat-icon"></i>
                    <div class="fs-3 fw-bold">{{ $activeOrders }}</div>
                    <div class="small opacity-75">Active orders</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card stat-blue p-4">
                    <i class="bi bi-check2-circle stat-icon"></i>
                    <div class="fs-3 fw-bold">{{ $completedOrders }}</div>
                    <div class="small opacity-75">Completed</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card stat-purple p-4">
                    <i class="bi bi-heart-fill stat-icon"></i>
                    <div class="fs-3 fw-bold">{{ $favoriteCount }}</div>
                    <div class="small opacity-75">Saved favorites</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-ml p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Recent orders</h5>
                        <a href="{{ route('orders.index') }}" class="small text-decoration-none">View all</a>
                    </div>
                    @forelse ($recentOrders as $order)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <a href="{{ route('orders.show', $order) }}" class="fw-semibold text-decoration-none text-dark">{{ $order->order_number }}</a>
                                <div class="small text-muted">{{ $order->farmer_name }} · {{ $order->market_name }} · {{ $order->pickup_date?->format('M j') }}</div>
                            </div>
                            <div class="text-end">
                                <span class="status-pill status-{{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span>
                                <div class="small fw-semibold">${{ number_format($order->total_amount, 2) }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No orders yet. <a href="{{ route('products.index') }}">Start shopping →</a></p>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-ml p-4 mb-4">
                    <h6 class="fw-bold mb-3">Quick actions</h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('products.index') }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-search me-1"></i> Browse products</a>
                        <a href="{{ route('cart.index') }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-cart3 me-1"></i> View cart</a>
                        <a href="{{ route('favorites.index') }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-heart me-1"></i> Favorites</a>
                        <a href="{{ route('profile.edit') }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-person-gear me-1"></i> Profile settings</a>
                    </div>
                </div>
                <div class="card-ml p-4">
                    <h6 class="fw-bold mb-2">Lifetime spend</h6>
                    <div class="fs-3 fw-bold text-ml-green">${{ number_format($spent, 2) }}</div>
                    <p class="small text-muted mb-0">Paid in person at pickup.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

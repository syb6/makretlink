@extends('layouts.app')

@section('title', 'Farmer Dashboard')

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <div class="mb-4 d-md-flex justify-content-between align-items-center">
            <div>
                <span class="badge-sub">FARMER MANAGEMENT PORTAL</span>
                <h1 class="h2 mb-1">{{ $farmer->business_name }}</h1>
                <p class="text-muted mb-0">Manage your products, track orders, and view customer reviews.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <span class="status-pill status-{{ $farmer->approval_status }} align-self-center">{{ $farmer->approval_status }}</span>
                <a href="{{ route('farmer.products.index') }}" class="btn btn-ml"><i class="bi bi-plus-circle me-1"></i> Add New Product</a>
            </div>
        </div>

        @if (! $farmer->isApproved())
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>Your stall is not approved yet — an administrator must approve it before your products appear publicly.
            </div>
        @endif

        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                    <div><div class="stat-label">Total Revenue</div><div class="stat-number">Rs {{ number_format($revenue, 2) }}</div></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div><div class="stat-label">Pending Orders</div><div class="stat-number">{{ $pendingOrders }}</div></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
                    <div><div class="stat-label">Active Products</div><div class="stat-number">{{ $productCount }}</div></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-bag-check"></i></div>
                    <div><div class="stat-label">Total Orders</div><div class="stat-number">{{ $totalOrders }}</div></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="dashboard-card h-100">
                    <div class="dashboard-card-header">
                        <h3 class="h5 mb-0">Recent Orders</h3>
                        <a href="{{ route('farmer.orders.index') }}" class="btn btn-sm btn-outline-ml">Manage Orders</a>
                    </div>
                    <div class="dashboard-card-body">
                        @forelse ($recentOrders as $order)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                    <a href="{{ route('farmer.orders.show', $order) }}" class="fw-bold text-decoration-none">{{ $order->order_number }}</a>
                                    <span class="status-pill status-{{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle" style="width:36px;height:36px;font-size:.8rem">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($order->customer?->user?->name ?? 'G', 0, 2)) }}</div>
                                        <div>
                                            <strong class="d-block small">{{ $order->customer?->user?->name ?? 'Guest' }}</strong>
                                            <small class="text-muted">Pickup: {{ $order->pickup_date?->format('D, M j') }} &middot; {{ $order->pickup_start_time?->format('g:i A') }}</small>
                                        </div>
                                    </div>
                                    <strong>Rs {{ number_format($order->total_amount, 2) }}</strong>
                                </div>
                            </div>
                        @empty
                            <div class="empty">
                                <i class="bi bi-check-circle text-success"></i>
                                <strong>No orders yet.</strong>
                                <p class="small mb-0">Publish weekly stock so customers can pre-order from you.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="dashboard-card mb-4">
                    <div class="dashboard-card-header"><h3 class="h5 mb-0"><i class="bi bi-trophy text-success me-1"></i> Best Sellers</h3></div>
                    <div class="dashboard-card-body">
                        @forelse ($bestSellers as $b)
                            <div class="d-flex align-items-center gap-3 p-2 mb-2 border rounded">
                                <div class="stat-icon" style="width:36px;height:36px;font-size:1rem"><i class="bi bi-basket"></i></div>
                                <div class="flex-grow-1">
                                    <strong class="d-block small">{{ $b->product_name }}</strong>
                                    <small class="text-muted">{{ $b->total_qty }} sold · Rs {{ number_format($b->total_sales, 2) }}</small>
                                </div>
                            </div>
                        @empty
                            <div class="empty"><i class="bi bi-basket2"></i><span class="small">No sales data yet.</span></div>
                        @endforelse
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="dashboard-card-header"><h3 class="h5 mb-0">Manage Your Stall</h3></div>
                    <div class="dashboard-card-body d-flex flex-column gap-2">
                        <a href="{{ route('farmer.stalls.index') }}" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-shop text-success me-2"></i> My Stalls &amp; Markets</a>
                        <a href="{{ route('farmer.products.index') }}" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-box-seam text-success me-2"></i> Products</a>
                        <a href="{{ route('farmer.stock.index') }}" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-boxes text-success me-2"></i> Weekly Stock &amp; Templates</a>
                        <a href="{{ route('farmer.slots.index') }}" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-calendar-week text-success me-2"></i> Pickup Slots</a>
                        <a href="{{ route('farmer.reviews') }}" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-chat-square-text text-success me-2"></i> Reviews</a>
                        <a href="{{ route('farmer.profile.edit') }}" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-person-gear text-success me-2"></i> Stall Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

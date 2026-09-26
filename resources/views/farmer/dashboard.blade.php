@extends('layouts.app')

@section('title', 'Farmer Dashboard')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 class="section-title mb-1">{{ $farmer->business_name }}</h1>
                <span class="status-pill status-{{ $farmer->approval_status }}">{{ $farmer->approval_status }}</span>
            </div>
            <a href="{{ route('farmer.profile.edit') }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-pencil me-1"></i>Edit profile</a>
        </div>

        @if ($farmer->approval_status !== 'approved')
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>Your stall is not approved yet — an administrator must approve it before your products appear publicly.
            </div>
        @endif

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
                    <div class="fs-3 fw-bold">{{ $pendingOrders }}</div>
                    <div class="small opacity-75">Pending acceptance</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card stat-blue p-4">
                    <i class="bi bi-cash-coin stat-icon"></i>
                    <div class="fs-3 fw-bold">${{ number_format($revenue, 2) }}</div>
                    <div class="small opacity-75">Revenue (accepted+)</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card stat-purple p-4">
                    <i class="bi bi-box-seam stat-icon"></i>
                    <div class="fs-3 fw-bold">{{ $productCount }}</div>
                    <div class="small opacity-75">Products</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card-ml p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Recent orders</h5>
                        <a href="{{ route('farmer.orders.index') }}" class="small text-decoration-none">Manage orders →</a>
                    </div>
                    @forelse ($recentOrders as $order)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <a href="{{ route('farmer.orders.show', $order) }}" class="fw-semibold text-decoration-none text-dark">{{ $order->order_number }}</a>
                                <div class="small text-muted">{{ $order->customer?->user?->name ?? 'Guest' }} · {{ $order->pickup_date?->format('M j') }}</div>
                            </div>
                            <div class="text-end">
                                <span class="status-pill status-{{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span>
                                <div class="small fw-semibold">${{ number_format($order->total_amount, 2) }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No orders yet.</p>
                    @endforelse
                </div>

                <div class="card-ml p-4">
                    <h5 class="fw-bold mb-3">Best sellers</h5>
                    @forelse ($bestSellers as $b)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>{{ $b->product_name }}</span>
                            <span class="text-muted small">{{ $b->total_qty }} sold · ${{ number_format($b->total_sales, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No sales data yet.</p>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card-ml p-4 mb-4">
                    <h6 class="fw-bold mb-3">Manage your stall</h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('farmer.stalls.index') }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-shop me-1"></i> My stalls & markets</a>
                        <a href="{{ route('farmer.products.index') }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-box-seam me-1"></i> Products</a>
                        <a href="{{ route('farmer.stock.index') }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-boxes me-1"></i> Weekly stock & templates</a>
                        <a href="{{ route('farmer.orders.index') }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-bag-check me-1"></i> Pre-orders</a>
                        <a href="{{ route('farmer.slots.index') }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-calendar-week me-1"></i> Pickup slots</a>
                        <a href="{{ route('farmer.reviews') }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-chat-square-text me-1"></i> Reviews</a>
                    </div>
                </div>
                <div class="card-ml p-4">
                    <h6 class="fw-bold mb-3">Tips for a busy market day</h6>
                    <ul class="small text-muted mb-0 ps-3">
                        <li class="mb-1">Publish weekly stock early — customers pre-order against it.</li>
                        <li class="mb-1">Set generous pickup slots with clear cutoff times.</li>
                        <li class="mb-0">Accept orders promptly to keep your rating high.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

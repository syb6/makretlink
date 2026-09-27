@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('admin_title', 'Admin Dashboard')

@section('content')
<div class="app-container">
    @include('layouts.partials.admin-sidebar')
    <div class="main-wrapper">
        @include('layouts.partials.admin-header')

        <main class="p-3 p-md-4">
            <div class="mb-4">
                <h2 class="h3 mb-1">System Overview</h2>
                <p class="text-muted mb-0">Monitor platform activity, pending approvals, and top performers.</p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-people"></i></div>
                        <div><div class="stat-number">{{ $totalCustomers }}</div><div class="stat-label">Total Customers ({{ $activeCustomers }} active)</div></div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-shop-window"></i></div>
                        <div><div class="stat-number">{{ $totalFarmers }}</div><div class="stat-label">Registered Farmers ({{ $pendingFarmers }} pending)</div></div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-geo-alt"></i></div>
                        <div><div class="stat-number">{{ $totalOrders }}</div><div class="stat-label">Orders ({{ $pendingOrders }} to accept)</div></div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                        <div><div class="stat-number">Rs {{ number_format($revenue, 2) }}</div><div class="stat-label">Platform Volume</div></div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="dashboard-card h-100">
                        <div class="dashboard-card-header">
                            <h3 class="h5 mb-0">Recent Platform Orders</h3>
                            <a href="{{ route('admin.reports') }}" class="btn btn-sm btn-outline-ml">Reports</a>
                        </div>
                        <div class="dashboard-card-body">
                            @forelse ($recentOrders as $order)
                                <div class="order-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <strong>{{ $order->order_number }}</strong>
                                        <div class="small text-muted">{{ $order->customer?->user?->name ?? 'Guest' }} &rarr; from {{ $order->market_name }}</div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <strong>Rs {{ number_format($order->total_amount, 2) }}</strong>
                                        <span class="status-pill status-{{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="empty"><i class="bi bi-receipt"></i><span>No orders yet.</span></div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <h3 class="h5 mb-0"><i class="bi bi-shield-lock text-success me-1"></i> Pending Approvals</h3>
                        </div>
                        <div class="dashboard-card-body">
                            <div class="p-3 mb-3 rounded" style="background: var(--accent-warn-soft); border-left: 3px solid var(--accent-warn)">
                                <strong class="d-block small">{{ $pendingFarmers }} farmer{{ $pendingFarmers === 1 ? '' : 's' }} waiting</strong>
                                <span class="small text-muted">Review new stall applications before they can list products.</span>
                            </div>
                            <a href="{{ route('admin.users', ['role' => 'farmer']) }}?status=pending" class="btn btn-ml w-100">Review applications</a>
                            <hr class="my-3">
                            <div class="d-grid gap-2">
                                <a href="{{ route('admin.markets') }}" class="btn btn-sm btn-outline-ml"><i class="bi bi-shop me-1"></i> Manage markets</a>
                                <a href="{{ route('admin.categories') }}" class="btn btn-sm btn-outline-ml"><i class="bi bi-tags me-1"></i> Manage categories</a>
                                <a href="{{ route('admin.announcements') }}" class="btn btn-sm btn-outline-ml"><i class="bi bi-megaphone me-1"></i> Broadcast announcement</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="admin-footer">
            <div class="container text-center">
                <p class="text-muted small mb-1">System Administration Portal &bull; Connecting Local Farmers &amp; Community Customers</p>
                <p class="text-muted small mb-0">&copy; {{ now()->year }} MarketLink. All Rights Reserved. Built for TechWiz 7.</p>
            </div>
        </footer>
    </div>
</div>
@endsection

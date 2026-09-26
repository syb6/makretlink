@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        {{-- Sidebar --}}
        <div class="col-lg-2 col-md-3">
            @include('layouts.partials.admin-sidebar')
        </div>

        <div class="col-lg-10 col-md-9">
            <h1 class="section-title mb-4">Admin <span class="accent">Dashboard</span></h1>

            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card stat-green p-4">
                        <i class="bi bi-person-workspace stat-icon"></i>
                        <div class="fs-3 fw-bold">{{ $totalFarmers }}</div>
                        <div class="small opacity-75">Farmers ({{ $pendingFarmers }} pending)</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card stat-blue p-4">
                        <i class="bi bi-people stat-icon"></i>
                        <div class="fs-3 fw-bold">{{ $totalCustomers }}</div>
                        <div class="small opacity-75">Customers ({{ $activeCustomers }} active)</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card stat-amber p-4">
                        <i class="bi bi-bag-check stat-icon"></i>
                        <div class="fs-3 fw-bold">{{ $totalOrders }}</div>
                        <div class="small opacity-75">Orders ({{ $pendingOrders }} to accept)</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card stat-purple p-4">
                        <i class="bi bi-cash-coin stat-icon"></i>
                        <div class="fs-3 fw-bold">${{ number_format($revenue, 2) }}</div>
                        <div class="small opacity-75">Platform revenue</div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card-ml p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Latest orders</h5>
                            <a href="{{ route('admin.reports') }}" class="small text-decoration-none">Reports →</a>
                        </div>
                        @forelse ($recentOrders as $order)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <strong class="small">{{ $order->order_number }}</strong>
                                    <div class="small text-muted">{{ $order->customer?->user?->name ?? 'Guest' }} · {{ $order->market_name }}</div>
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
                </div>
                <div class="col-lg-5">
                    <div class="card-ml p-4">
                        <h5 class="fw-bold mb-3">Quick actions</h5>
                        <div class="d-grid gap-2">
                            <a href="{{ route('admin.users', ['role' => 'farmer']) }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-person-check me-1"></i> Approve farmers ({{ $pendingFarmers }})</a>
                            <a href="{{ route('admin.markets') }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-shop me-1"></i> Manage markets</a>
                            <a href="{{ route('admin.moderation') }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-shield-exclamation me-1"></i> Content moderation</a>
                            <a href="{{ route('admin.announcements') }}" class="btn btn-outline-ml btn-sm"><i class="bi bi-megaphone me-1"></i> Announcements</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

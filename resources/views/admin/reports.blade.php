@extends('layouts.app')

@section('title', 'Reports & Analytics')
@section('admin_title', 'Reports & Analytics')

@section('content')
<div class="app-container">
    @include('layouts.partials.admin-sidebar')
    <div class="main-wrapper">
        @include('layouts.partials.admin-header')

        <main class="p-3 p-md-4">
            <div class="mb-4">
                <h2 class="h3 mb-1">Reports &amp; Analytics</h2>
                <p class="text-muted mb-0">Platform-wide revenue, order flow and top performers.</p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="dashboard-card h-100">
                        <div class="dashboard-card-header"><h3 class="h5 mb-0">Orders by status</h3></div>
                        <div class="dashboard-card-body">
                            @forelse ($ordersByStatus as $row)
                                <div class="d-flex justify-content-between border-bottom py-2">
                                    <span class="status-pill status-{{ $row->status }}">{{ str_replace('_', ' ', $row->status) }}</span>
                                    <span>{{ $row->count }} orders · Rs {{ number_format($row->total, 2) }}</span>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">No orders yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="dashboard-card h-100">
                        <div class="dashboard-card-header"><h3 class="h5 mb-0">Totals</h3></div>
                        <div class="dashboard-card-body">
                            <div class="metric-box mb-3">
                                <div class="icon-sq"><i class="bi bi-cash-coin"></i></div>
                                <div>
                                    <div class="fs-4 fw-bold">Rs {{ number_format($totalRevenue, 2) }}</div>
                                    <div class="small text-muted">Revenue from accepted / ready / completed orders</div>
                                </div>
                            </div>
                            <div class="metric-box">
                                <div class="icon-sq"><i class="bi bi-receipt"></i></div>
                                <div>
                                    <div class="fs-4 fw-bold">{{ $totalOrders }}</div>
                                    <div class="small text-muted mb-0">Total orders placed platform-wide</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="dashboard-card h-100">
                        <div class="dashboard-card-header"><h3 class="h5 mb-0">Revenue by market</h3></div>
                        <div class="dashboard-card-body">
                            @forelse ($revenueByMarket as $row)
                                <div class="d-flex justify-content-between border-bottom py-2">
                                    <span>{{ $row->name }}</span>
                                    <span class="text-muted small">{{ $row->orders }} orders · <strong class="text-dark">Rs {{ number_format($row->revenue, 2) }}</strong></span>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">No data yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="dashboard-card h-100">
                        <div class="dashboard-card-header"><h3 class="h5 mb-0">Most active farmers</h3></div>
                        <div class="dashboard-card-body">
                            @forelse ($mostActiveFarmers as $row)
                                <div class="d-flex justify-content-between border-bottom py-2">
                                    <span>{{ $row->business_name }}</span>
                                    <span class="text-muted small">{{ $row->orders }} orders · <strong class="text-dark">Rs {{ number_format($row->revenue, 2) }}</strong></span>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">No data yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="admin-footer">
            <div class="container text-center">
                <p class="text-muted small mb-0">&copy; {{ now()->year }} MarketLink Administration Portal.</p>
            </div>
        </footer>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Reports & Analytics')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        <div class="col-lg-2 col-md-3">@include('layouts.partials.admin-sidebar')</div>

        <div class="col-lg-10 col-md-9">
            <h1 class="section-title mb-4">Reports & <span class="accent">Analytics</span></h1>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card-ml p-4 h-100">
                        <h5 class="fw-bold mb-3">Orders by status</h5>
                        @forelse ($ordersByStatus as $row)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="status-pill status-{{ $row->status }}">{{ str_replace('_', ' ', $row->status) }}</span>
                                <span>{{ $row->count }} orders · ${{ number_format($row->total, 2) }}</span>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">No orders yet.</p>
                        @endforelse
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card-ml p-4 h-100">
                        <h5 class="fw-bold mb-3">Totals</h5>
                        <div class="fs-3 fw-bold text-ml-green">${{ number_format($totalRevenue, 2) }}</div>
                        <p class="text-muted small">Revenue from accepted / ready / completed orders</p>
                        <div class="fs-3 fw-bold">{{ $totalOrders }}</div>
                        <p class="text-muted small mb-0">Total orders placed platform-wide</p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card-ml p-4 h-100">
                        <h5 class="fw-bold mb-3">Revenue by market</h5>
                        @forelse ($revenueByMarket as $row)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span>{{ $row->name }}</span>
                                <span class="text-muted small">{{ $row->orders }} orders · <strong class="text-dark">${{ number_format($row->revenue, 2) }}</strong></span>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">No data yet.</p>
                        @endforelse
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card-ml p-4 h-100">
                        <h5 class="fw-bold mb-3">Most active farmers</h5>
                        @forelse ($mostActiveFarmers as $row)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span>{{ $row->business_name }}</span>
                                <span class="text-muted small">{{ $row->orders }} orders · <strong class="text-dark">${{ number_format($row->revenue, 2) }}</strong></span>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">No data yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

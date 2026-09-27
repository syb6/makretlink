@extends('layouts.app')

@section('title', 'My Pre-Orders')

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <span class="badge-sub">CUSTOMER MARKET PORTAL</span>
        <h1 class="section-title mb-4">My Pre-Orders</h1>

        <div class="dashboard-card flush">
            <div class="table-responsive">
                <table class="table table-ml align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Farmer / Market</th>
                            <th>Pickup</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('orders.show', $order) }}" class="fw-semibold text-decoration-none">{{ $order->order_number }}</a>
                                    <div class="small text-muted">{{ $order->placed_at?->format('M j, Y g:i A') }}</div>
                                </td>
                                <td class="small">{{ $order->farmer_name }}<br><span class="text-muted">{{ $order->market_name }}</span></td>
                                <td class="small">{{ $order->pickup_date?->format('D, M j') }}<br><span class="text-muted">{{ $order->pickup_start_time?->format('g:i A') }}–{{ $order->pickup_end_time?->format('g:i A') }}</span></td>
                                <td class="fw-semibold">Rs {{ number_format($order->total_amount, 2) }}</td>
                                <td><span class="status-pill status-{{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-ml">Details</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $orders->links() }}</div>
        </div>
    </div>
</section>
@endsection

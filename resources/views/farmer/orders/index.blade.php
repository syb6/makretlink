@extends('layouts.app')

@section('title', 'Pre-Orders')

@section('content')
<section class="py-5">
    <div class="container">
        <h1 class="section-title mb-4">Incoming <span class="accent">Pre-Orders</span></h1>

        <div class="d-flex gap-2 flex-wrap mb-3">
            <a href="{{ route('farmer.orders.index') }}" class="btn btn-sm {{ ! $status ? 'btn-ml' : 'btn-outline-ml' }}">All</a>
            @foreach ($statuses as $s)
                <a href="?status={{ $s }}" class="btn btn-sm {{ $status === $s ? 'btn-ml' : 'btn-outline-ml' }}">{{ str_replace('_', ' ', $s) }}</a>
            @endforeach
        </div>

        <div class="card-ml p-2">
            <div class="table-responsive">
                <table class="table table-ml align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Pickup</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('farmer.orders.show', $order) }}" class="fw-semibold text-decoration-none">{{ $order->order_number }}</a>
                                    <div class="small text-muted">{{ $order->placed_at?->format('M j, g:i A') }}</div>
                                </td>
                                <td class="small">{{ $order->customer?->user?->name ?? 'Guest' }}<br><span class="text-muted">{{ $order->customer_phone }}</span></td>
                                <td class="small">{{ $order->pickup_date?->format('M j') }}<br><span class="text-muted">{{ $order->pickup_start_time?->format('g:i A') }}–{{ $order->pickup_end_time?->format('g:i A') }}</span></td>
                                <td class="fw-semibold">${{ number_format($order->total_amount, 2) }}</td>
                                <td><span class="status-pill status-{{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span></td>
                                <td class="text-end">
                                    @if ($order->status === 'placed')
                                        <form method="POST" action="{{ route('farmer.orders.accept', $order) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Accept</button>
                                        </form>
                                        <form method="POST" action="{{ route('farmer.orders.decline', $order) }}" class="d-inline" data-confirm="Decline this order?">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    @elseif ($order->status === 'accepted')
                                        <form method="POST" action="{{ route('farmer.orders.ready', $order) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-ml"><i class="bi bi-box-seam"></i> Mark ready</button>
                                        </form>
                                    @elseif ($order->status === 'ready_for_pickup')
                                        <form method="POST" action="{{ route('farmer.orders.complete', $order) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success">Complete</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('farmer.orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No orders in this view.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-2">{{ $orders->links() }}</div>
        </div>
    </div>
</section>
@endsection

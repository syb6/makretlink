@extends('layouts.app')

@section('title', 'Order '.$order->order_number)

@section('content')
<section class="py-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ route('farmer.orders.index') }}">Pre-orders</a></li>
                <li class="breadcrumb-item active">{{ $order->order_number }}</li>
            </ol>
        </nav>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-ml p-4 mb-4">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div>
                            <h1 class="h4 fw-bold">{{ $order->order_number }}</h1>
                            <span class="status-pill status-{{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span>
                        </div>
                        <div class="text-end">
                            <div class="fs-4 fw-bold text-ml-green">${{ number_format($order->total_amount, 2) }}</div>
                            <small class="text-muted">collected at pickup</small>
                        </div>
                    </div>
                </div>

                <div class="card-ml p-4 mb-4">
                    <h5 class="fw-bold mb-3">Items</h5>
                    @foreach ($order->items as $item)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>{{ $item->product_name }} <span class="text-muted small">× {{ $item->quantity }} {{ $item->unit }}</span></span>
                            <span class="fw-semibold">${{ number_format($item->subtotal, 2) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="card-ml p-4">
                    <h5 class="fw-bold mb-3">Status history</h5>
                    <ul class="list-unstyled mb-0">
                        @foreach ($order->statusHistories->sortBy('changed_at') as $h)
                            <li class="d-flex gap-3 mb-2">
                                <span class="status-pill status-{{ $h->status }}">{{ str_replace('_', ' ', $h->status) }}</span>
                                <div class="small">
                                    <strong>{{ $h->changed_at?->format('M j, g:i A') }}</strong>
                                    @if ($h->changedBy) <span class="text-muted">by {{ $h->changedBy->name }}</span>@endif
                                    @if ($h->note) <div class="text-muted">{{ $h->note }}</div>@endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-ml p-4 mb-4">
                    <h6 class="fw-bold mb-3">Customer</h6>
                    <p class="mb-1"><strong>{{ $order->customer_name }}</strong></p>
                    <p class="small mb-1"><i class="bi bi-envelope me-2"></i>{{ $order->customer_email }}</p>
                    <p class="small mb-0"><i class="bi bi-telephone me-2"></i>{{ $order->customer_phone ?? '—' }}</p>
                    @if ($order->customer_note)
                        <div class="alert bg-ml-green-light small mt-3 mb-0"><strong>Note:</strong> {{ $order->customer_note }}</div>
                    @endif
                </div>

                <div class="card-ml p-4">
                    <h6 class="fw-bold mb-3">Actions</h6>
                    <div class="d-grid gap-2">
                        @if ($order->status === 'placed')
                            <form method="POST" action="{{ route('farmer.orders.accept', $order) }}">@csrf
                                <button class="btn btn-success w-100"><i class="bi bi-check-lg me-1"></i>Accept order</button></form>
                            <form method="POST" action="{{ route('farmer.orders.decline', $order) }}" data-confirm="Decline this order?">@csrf
                                <button class="btn btn-outline-danger w-100"><i class="bi bi-x-lg me-1"></i>Decline</button></form>
                        @elseif ($order->status === 'accepted')
                            <form method="POST" action="{{ route('farmer.orders.ready', $order) }}">@csrf
                                <button class="btn btn-ml w-100"><i class="bi bi-box-seam me-1"></i>Mark ready for pickup</button></form>
                        @elseif ($order->status === 'ready_for_pickup')
                            <form method="POST" action="{{ route('farmer.orders.complete', $order) }}">@csrf
                                <button class="btn btn-success w-100"><i class="bi bi-check2-all me-1"></i>Complete (picked up & paid)</button></form>
                        @else
                            <span class="text-muted small">No actions available.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

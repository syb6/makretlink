@extends('layouts.app')

@section('title', 'Order '.$order->order_number)

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">My Pre-Orders</a></li>
                <li class="breadcrumb-item active">{{ $order->order_number }}</li>
            </ol>
        </nav>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="dashboard-card mb-4">
                    <div class="dashboard-card-body">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                            <div>
                                <h1 class="h4 fw-bold mb-1">{{ $order->order_number }}</h1>
                                <span class="status-pill status-{{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span>
                            </div>
                            <div class="text-end">
                                <div class="fs-4 fw-bold text-ml-green">Rs {{ number_format($order->total_amount, 2) }}</div>
                                <small class="text-muted">Pay at pickup</small>
                            </div>
                        </div>

                        @if ($order->status === 'completed' && (! $existingProductReview->count() || ! $existingFarmerReview))
                            <div class="alert p-3 small mb-3" style="background: var(--primary-light); border-left: 3px solid var(--primary); color: var(--primary-contrast);">
                                <i class="bi bi-star me-1"></i><strong>How was it?</strong>
                                Glad your order made it — leave a rating for the items{{ $order->farmerMarket ? ' and the farmer' : '' }} below.
                            </div>
                        @endif

                        <div class="row g-3 small">
                            <div class="col-md-6">
                                <div class="text-muted">Pickup at</div>
                                <strong>{{ $order->market_name }}</strong> — {{ $order->farmer_name }}<br>
                                {{ $order->pickup_date?->format('l, F j, Y') }}<br>
                                {{ \Carbon\Carbon::parse($order->pickup_start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($order->pickup_end_time)->format('g:i A') }}
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted">Cutoff for changes</div>
                                @if ($order->pickupSlot?->cutoff_at)
                                    <strong>{{ $order->pickupSlot->cutoff_at->format('M j, g:i A') }}</strong>
                                @else
                                    <span class="text-muted">See pickup window above.</span>
                                @endif
                            </div>
                        </div>

                        @if ($order->customer_note)
                            <div class="reply-box small mt-3"><strong>Your note:</strong> {{ $order->customer_note }}</div>
                        @endif
                    </div>
                </div>

                <div class="dashboard-card mb-4">
                    <div class="dashboard-card-header"><h3 class="h5 mb-0">Items</h3></div>
                    <div class="dashboard-card-body">
                        @foreach ($order->items as $item)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2 gap-2 flex-wrap">
                                <div>
                                    <span class="fw-semibold">{{ $item->product_name }}</span>
                                    <span class="text-muted small">× {{ $item->quantity }} {{ $item->unit }} @ Rs {{ number_format($item->unit_price, 2) }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fw-semibold">Rs {{ number_format($item->subtotal, 2) }}</span>
                                    @if ($order->status === 'completed' && $item->product_id && ! $existingProductReview->has($item->product_id))
                                        <button class="btn btn-sm btn-outline-ml" data-bs-toggle="modal" data-bs-target="#reviewModal-{{ $item->id }}">Review</button>
                                    @elseif ($existingProductReview->has($item->product_id))
                                        <span class="badge badge-soft">Reviewed ✓</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        @if ($order->status === 'completed' && $order->farmerMarket && ! $existingFarmerReview)
                            <button class="btn btn-outline-ml mt-3" data-bs-toggle="modal" data-bs-target="#farmerReviewModal">Rate {{ $order->farmer_name }}</button>
                        @elseif ($existingFarmerReview)
                            <div class="reply-box small mt-3">You rated this farmer {{ $existingFarmerReview->rating }}/5. Thank you!</div>
                        @endif
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="dashboard-card-header"><h3 class="h5 mb-0">Status history</h3></div>
                    <div class="dashboard-card-body">
                        <ul class="list-unstyled mb-0">
                            @foreach ($order->statusHistories->sortBy('changed_at') as $h)
                                <li class="d-flex gap-3 mb-3">
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
            </div>

            <div class="col-lg-4">
                <div class="card-ml p-4">
                    <h6 class="fw-bold mb-3">Actions</h6>
                    <div class="d-grid gap-2">
                        @if (in_array($order->status, ['placed', 'accepted']))
                            <form method="POST" action="{{ route('orders.cancel', $order) }}" data-confirm="Cancel this order?">
                                @csrf
                                <button class="btn btn-outline-danger w-100"><i class="bi bi-x-circle me-1"></i> Cancel order</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('orders.reorder', $order) }}">
                            @csrf
                            <button class="btn btn-outline-ml w-100"><i class="bi bi-arrow-repeat me-1"></i> Reorder items</button>
                        </form>
                        @if ($order->farmerMarket?->market)
                            <a href="{{ route('markets.show', $order->farmerMarket->market) }}" class="btn btn-outline-ml"><i class="bi bi-geo-alt me-1"></i> View market</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Product review modals --}}
@foreach ($order->items as $item)
    @if ($order->status === 'completed' && $item->product_id && ! $existingProductReview->has($item->product_id))
        <div class="modal fade" id="reviewModal-{{ $item->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('orders.review.product', [$order, $item]) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Review {{ $item->product_name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label fw-semibold">Rating</label>
                        <select name="rating" class="form-select mb-3" required>
                            <option value="5">★★★★★ Excellent</option>
                            <option value="4">★★★★ Good</option>
                            <option value="3">★★★ Average</option>
                            <option value="2">★★ Poor</option>
                            <option value="1">★ Terrible</option>
                        </select>
                        <label class="form-label fw-semibold">Comment</label>
                        <textarea name="comment" rows="3" class="form-control" placeholder="What did you think?"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-ml">Submit review</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach

@if ($order->status === 'completed' && $order->farmerMarket && ! $existingFarmerReview)
    <div class="modal fade" id="farmerReviewModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('orders.review.farmer', $order) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Rate {{ $order->farmer_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Rating</label>
                    <select name="rating" class="form-select mb-3" required>
                        <option value="5">★★★★★ Excellent</option>
                        <option value="4">★★★★ Good</option>
                        <option value="3">★★★ Average</option>
                        <option value="2">★★ Poor</option>
                        <option value="1">★ Terrible</option>
                    </select>
                    <label class="form-label fw-semibold">Comment</label>
                    <textarea name="comment" rows="3" class="form-control"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-ml">Submit rating</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection

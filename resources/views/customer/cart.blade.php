@extends('layouts.app')

@section('title', 'Pre-Order Basket')

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <span class="badge-sub">CUSTOMER MARKET PORTAL</span>
        <h1 class="section-title mb-4">Your Pre-Order Basket</h1>

        @if ($cart->items->isEmpty())
            <div class="dashboard-card">
                <div class="empty">
                    <i class="bi bi-basket2"></i>
                    <strong>Your cart is empty.</strong>
                    <p class="small mb-2">Reserve fresh produce before market day.</p>
                    <a href="{{ route('products.index') }}" class="btn btn-ml btn-sm">Browse fresh produce <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        @else
            <div class="row g-4">
                <div class="col-lg-8">
                    @foreach ($cart->items->groupBy('farmerMarket.market.name') as $marketName => $items)
                        <div class="dashboard-card mb-3">
                            <div class="dashboard-card-header">
                                <h3 class="h6 mb-0"><i class="bi bi-geo-alt me-1" style="color: var(--primary-dark)"></i>{{ $marketName }}</h3>
                            </div>
                            <div class="dashboard-card-body py-2">
                                @foreach ($items as $item)
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-3 gap-3 flex-wrap">
                                        <div class="flex-grow-1">
                                            <a href="{{ route('products.show', $item->product) }}" class="fw-semibold text-decoration-none">{{ $item->product->name }}</a>
                                            <div class="small text-muted">{{ $item->farmerMarket->stall_name ?? $item->product->farmer->business_name }} · Rs {{ number_format($item->price, 2) }} / {{ $item->product->unit }}</div>
                                        </div>
                                        <form method="POST" action="{{ route('cart.update', $item) }}" class="d-flex gap-2 align-items-center">
                                            @csrf
                                            <input type="number" name="quantity" value="{{ $item->quantity }}" min="0.5" step="0.5" class="form-control form-control-sm" style="width:90px" aria-label="Quantity">
                                            <button class="btn btn-sm btn-outline-ml">Update</button>
                                        </form>
                                        <form method="POST" action="{{ route('cart.remove', $item) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger" title="Remove" aria-label="Remove"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="col-lg-4">
                    <div class="card-ml p-4">
                        <h5 class="fw-bold mb-3">Summary</h5>
                        @foreach ($cart->items->groupBy('farmerMarket.market.name') as $marketName => $items)
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">{{ $marketName }}</span>
                                <span>Rs {{ number_format($items->sum(fn ($i) => $i->quantity * $i->price), 2) }}</span>
                            </div>
                        @endforeach
                        <hr>
                        <div class="d-flex justify-content-between fw-bold mb-3">
                            <span>Total</span>
                            <span class="product-price">Rs {{ number_format($cart->total(), 2) }}</span>
                        </div>
                        <a href="{{ route('checkout.index') }}" class="btn btn-ml w-100">Proceed to checkout</a>
                        <p class="small text-muted text-center mt-2 mb-0"><i class="bi bi-shield-check me-1"></i>Pay the farmer at pickup.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection

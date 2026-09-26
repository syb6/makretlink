@extends('layouts.app')

@section('title', 'Cart')

@section('content')
<section class="py-5">
    <div class="container">
        <h1 class="section-title mb-4">Your <span class="accent">Cart</span></h1>

        @if ($cart->items->isEmpty())
            <div class="alert alert-light border">
                Your cart is empty. <a href="{{ route('products.index') }}">Browse fresh produce →</a>
            </div>
        @else
            <div class="row g-4">
                <div class="col-lg-8">
                    @foreach ($cart->items->groupBy('farmerMarket.market.name') as $marketName => $items)
                        <div class="card-ml p-3 mb-3">
                            <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt me-1"></i>{{ $marketName }}</h6>
                            @foreach ($items as $item)
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2 gap-3">
                                    <div class="flex-grow-1">
                                        <a href="{{ route('products.show', $item->product) }}" class="fw-semibold text-decoration-none text-dark">{{ $item->product->name }}</a>
                                        <div class="small text-muted">{{ $item->farmerMarket->stall_name ?? $item->product->farmer->business_name }} · ${{ number_format($item->price, 2) }} / {{ $item->product->unit }}</div>
                                    </div>
                                    <form method="POST" action="{{ route('cart.update', $item) }}" class="d-flex gap-2 align-items-center">
                                        @csrf
                                        <input type="number" name="quantity" value="{{ $item->quantity }}" min="0.5" step="0.5" class="form-control form-control-sm" style="width:90px">
                                        <button class="btn btn-sm btn-outline-secondary">Update</button>
                                    </form>
                                    <form method="POST" action="{{ route('cart.remove', $item) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                <div class="col-lg-4">
                    <div class="card-ml p-4">
                        <h5 class="fw-bold mb-3">Summary</h5>
                        @foreach ($cart->items->groupBy('farmerMarket.market.name') as $marketName => $items)
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">{{ $marketName }}</span>
                                <span>${{ number_format($items->sum(fn ($i) => $i->quantity * $i->price), 2) }}</span>
                            </div>
                        @endforeach
                        <hr>
                        <div class="d-flex justify-content-between fw-bold mb-3">
                            <span>Total</span>
                            <span class="product-price">${{ number_format($cart->total(), 2) }}</span>
                        </div>
                        <a href="{{ route('checkout.index') }}" class="btn btn-ml w-100">Proceed to checkout</a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection

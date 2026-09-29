@extends('layouts.app')

@section('title', $product->name)

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                <li class="breadcrumb-item active">{{ $product->name }}</li>
            </ol>
        </nav>

        <div class="row g-5">
            <div class="col-lg-6">
                @include('products._image', ['product' => $product, 'class' => 'w-100 rounded-4 shadow-sm', 'style' => 'max-height:420px;object-fit:cover', 'eager' => true])
            </div>
            <div class="col-lg-6">
                <span class="badge badge-soft mb-2">{{ $product->category?->name ?? 'General' }}</span>
                <h1 class="section-title">{{ $product->name }}</h1>
                <p class="text-muted">by
                    <a href="{{ route('farmers.show', $product->farmer) }}" class="fw-semibold text-decoration-none">{{ $product->farmer->business_name }}</a>
                </p>

                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="product-price fs-3">Rs {{ number_format($product->price, 2) }}</span>
                    <span class="text-muted">/ {{ $product->unit }}</span>
                </div>

                @if ($avgRating)
                    <div class="mb-3">
                        <span class="stars">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi {{ $i <= round($avgRating) ? 'bi-star-fill' : 'bi-star' }}"></i>
                            @endfor
                        </span>
                        <span class="small text-muted ms-1">{{ number_format($avgRating, 1) }} ({{ $reviewCount }} review{{ $reviewCount === 1 ? '' : 's' }})</span>
                    </div>
                @endif

                <p>{{ $product->description }}</p>

                @if ($stocks->count() > 1)
                    <div class="market-availability">
                        <div class="market-availability-title">
                            <i class="bi bi-geo-alt-fill me-1"></i>Same product, available at {{ $stocks->count() }} markets
                        </div>
                        <ul class="market-availability-list">
                            @foreach ($stocks as $s)
                                <li>
                                    <span>{{ $s->farmerMarket->market->name }}</span>
                                    <span class="text-muted small">{{ $s->available_quantity }} {{ $product->unit }} in stock</span>
                                </li>
                            @endforeach
                        </ul>
                        <p class="small text-muted mb-0 mt-2">
                            <i class="bi bi-info-circle me-1"></i>Pick whichever stall suits your week — stock is tracked per stall.
                        </p>
                    </div>
                @endif

                @auth
                    @if (auth()->user()->isCustomer())
                        <div class="card card-ml p-3 mb-3">
                            <label class="form-label fw-semibold small">Pick a stall / market stock</label>
                            <select id="stockSelect" class="form-select mb-2">
                                @forelse ($stocks as $s)
                                    <option value="{{ $s->id }}" data-available="{{ $s->available_quantity }}">
                                        {{ $s->farmerMarket->market->name }} — {{ $s->farmerMarket->stall_name ?? $s->farmerMarket->farmer->business_name }}
                                        ({{ $s->available_quantity }} {{ $product->unit }} available)
                                    </option>
                                @empty
                                    <option disabled>No stock available this week</option>
                                @endforelse
                            </select>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="number" id="qtyInput" class="form-control" style="max-width:110px" min="0.5" step="0.5" value="1">
                                <button class="btn btn-ml flex-grow-1 add-to-cart-btn" data-stock-id="{{ $stocks->first()?->id }}" data-product-name="{{ $product->name }}">
                                    <i class="bi bi-cart-plus me-1"></i> Add to cart
                                </button>
                                <button class="btn btn-outline-danger favorite-product-btn" data-product-id="{{ $product->id }}" data-url="{{ route('favorites.toggle.product') }}" title="Save to favorites" aria-label="Save to favorites">
                                    <i class="bi bi-heart"></i>
                                </button>
                            </div>
                        </div>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-ml btn-lg">Log in to pre-order</a>
                @endauth
            </div>
        </div>

        {{-- Reviews --}}
        <div class="mt-5">
            <h4 class="fw-bold mb-3">Customer reviews</h4>
            <div class="row g-3">
                @forelse ($reviews as $review)
                    <div class="col-md-6">
                        <div class="card-ml p-3 h-100">
                            <div class="d-flex justify-content-between gap-2">
                                <strong>{{ $review->customer->user->name }}</strong>
                                <span class="stars">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }} small"></i>
                                    @endfor
                                </span>
                            </div>
                            <p class="small mb-0 text-muted">{{ $review->comment }}</p>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><p class="text-muted small">No reviews yet. Reviews can be left after completing an order.</p></div>
                @endforelse
            </div>
        </div>

        @if ($related->isNotEmpty())
            <h4 class="fw-bold mt-5 mb-3">Related products</h4>
            <div class="row g-4">
                @foreach ($related as $rp)
                    <div class="col-6 col-md-3">
                        <a href="{{ route('products.show', $rp) }}" class="text-decoration-none">
                            <div class="product-card h-100">
                                @include('products._image', ['product' => $rp, 'class' => 'product-thumb'])
                                <div class="p-2">
                                    <div class="fw-semibold small">{{ $rp->name }}</div>
                                    <span class="product-price small">Rs {{ number_format($rp->price, 2) }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@push('scripts')
<script>
    (function () {
        var select = document.getElementById('stockSelect');
        var btn = document.querySelector('.add-to-cart-btn');
        if (select && btn) {
            select.addEventListener('change', function () {
                btn.dataset.stockId = select.value;
            });
        }
    })();
</script>
@endpush
@endsection

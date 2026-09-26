@php
    $product = $stock->product;
    $market = $stock->farmerMarket->market;
@endphp
<div class="col-sm-6 col-md-4 col-lg-3">
    <div class="product-card h-100 d-flex flex-column">
        <a href="{{ route('products.show', $product) }}" class="text-decoration-none">
            @include('products._image', ['product' => $product])
        </a>
        <div class="p-3 d-flex flex-column flex-grow-1">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <span class="badge badge-soft">{{ $product->category?->name ?? 'General' }}</span>
                <small class="text-muted"><i class="bi bi-geo-alt"></i> {{ Str::limit($market->name, 18) }}</small>
            </div>
            <a href="{{ route('products.show', $product) }}" class="fw-semibold text-dark text-decoration-none mb-1">{{ $product->name }}</a>
            <div class="small text-muted mb-2">
                by {{ $product->farmer->business_name }}
            </div>
            <div class="mt-auto d-flex justify-content-between align-items-center">
                <div>
                    <span class="product-price">${{ number_format($product->price, 2) }}</span>
                    <small class="text-muted">/ {{ $product->unit }}</small>
                </div>
                <div class="text-end">
                    <small class="text-muted d-block">{{ $stock->available_quantity }} {{ $product->unit }} left</small>
                    @auth
                        @if (auth()->user()->isCustomer())
                            <button class="btn btn-ml btn-sm mt-1 add-to-cart-btn"
                                    data-stock-id="{{ $stock->id }}"
                                    data-product-name="{{ $product->name }}">
                                <i class="bi bi-plus-lg"></i> Add
                            </button>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>

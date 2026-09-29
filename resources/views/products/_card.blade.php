@php
    $product = $stock->product;
    $market = $stock->farmerMarket->market;
    // $favoriteProductIds (set of ids) is passed by the controllers — no per-card query.
    $isFavProduct = isset($favoriteProductIds) && $favoriteProductIds->contains($product->id);
    // $marketCounts (product_id => distinct markets with live stock) is also
    // controller-computed — the same product stocked at two markets appears
    // once per market, so say so instead of looking like a duplicate.
    $marketCount = isset($marketCounts) ? ($marketCounts[$product->id] ?? 1) : 1;
@endphp
<div class="col-sm-6 col-md-4 col-lg-3">
    <div class="product-card h-100">
        <a href="{{ route('products.show', $product) }}" class="product-img-wrap">
            @include('products._image', ['product' => $product])
            <span class="product-badge">{{ $stock->available_quantity > 0 ? 'In Stock' : 'Sold Out' }}</span>
            @if ($marketCount > 1)
                <span class="product-badge product-badge-alt"><i class="bi bi-geo-alt-fill me-1"></i>{{ $marketCount }} markets</span>
            @endif
            @auth
                @if (auth()->user()->isCustomer())
                    <button type="button" class="fav-btn favorite-product-btn"
                            data-url="{{ route('favorites.toggle.product') }}"
                            data-product-id="{{ $product->id }}"
                            title="{{ $isFavProduct ? 'Remove from favorites' : 'Save to favorites' }}"
                            aria-label="Toggle favorite product">
                        <i class="bi {{ $isFavProduct ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                    </button>
                @endif
            @endauth
        </a>
        <div class="product-body">
            <span class="product-cat">{{ $product->category?->name ?? 'General' }}</span>
            <a href="{{ route('products.show', $product) }}" class="product-name">{{ $product->name }}</a>
            <div class="product-farmer">
                <i class="bi bi-person-circle me-1"></i>{{ $product->farmer->business_name }}
                <span class="d-block"><i class="bi bi-geo-alt me-1"></i>{{ \Illuminate\Support\Str::limit($market->name, 26) }}@if ($marketCount > 1) · +{{ $marketCount - 1 }} more @endif</span>
            </div>
            <div class="product-bottom">
                <span class="product-price">Rs {{ number_format($product->price, 2) }} <small>/{{ $product->unit }}</small></span>
                @auth
                    @if (auth()->user()->isCustomer())
                        <button class="product-add add-to-cart-btn" type="button"
                                data-stock-id="{{ $stock->id }}"
                                data-product-name="{{ $product->name }}"
                                title="Add {{ $product->name }} to cart" aria-label="Add to cart">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    @else
                        <a href="{{ route('products.show', $product) }}" class="product-add" title="View details"><i class="bi bi-arrow-right"></i></a>
                    @endif
                @else
                    <a href="{{ route('products.show', $product) }}" class="product-add" title="View details"><i class="bi bi-arrow-right"></i></a>
                @endauth
            </div>
        </div>
    </div>
</div>

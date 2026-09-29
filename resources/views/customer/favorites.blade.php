@extends('layouts.app')

@section('title', 'Favorites')

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <span class="badge-sub">CUSTOMER MARKET PORTAL</span>
        <h1 class="section-title mb-4">My Favorites</h1>

        <ul class="nav nav-pills mb-4 gap-2" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-products" type="button">Products ({{ $products->count() }})</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-farmers" type="button">Farmers ({{ $farmers->count() }})</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-markets" type="button">Markets ({{ $markets->count() }})</button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-products">
                <div class="row g-4">
                    @forelse ($products as $fav)
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <div class="product-card h-100">
                                @include('products._image', ['product' => $fav->product])
                                <div class="product-body">
                                    <a href="{{ route('products.show', $fav->product) }}" class="product-name">{{ $fav->product->name }}</a>
                                    <span class="product-price small">Rs {{ number_format($fav->product->price, 2) }}</span>
                                    <form method="POST" action="{{ route('favorites.toggle.product') }}" class="mt-2">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $fav->product_id }}">
                                        <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-heartbreak"></i> Remove</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12"><div class="ml-note"><i class="bi bi-info-circle"></i>No favorite products yet. Tap the ♥ on any product.</div></div>
                    @endforelse
                </div>
            </div>

            <div class="tab-pane fade" id="tab-farmers">
                <div class="row g-4">
                    @forelse ($farmers as $fav)
                        <div class="col-md-4">
                            <div class="card-ml p-3 d-flex justify-content-between align-items-center">
                                <a href="{{ route('farmers.show', $fav->farmer) }}" class="fw-semibold text-decoration-none">{{ $fav->farmer->business_name }}</a>
                                <form method="POST" action="{{ route('favorites.toggle.farmer') }}">
                                    @csrf
                                    <input type="hidden" name="farmer_id" value="{{ $fav->farmer_id }}">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-heartbreak"></i></button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-12"><div class="ml-note"><i class="bi bi-info-circle"></i>No favorite farmers yet.</div></div>
                    @endforelse
                </div>
            </div>

            <div class="tab-pane fade" id="tab-markets">
                <div class="row g-4">
                    @forelse ($markets as $fav)
                        <div class="col-md-4">
                            <div class="card-ml p-3 d-flex justify-content-between align-items-center">
                                <a href="{{ route('markets.show', $fav->market) }}" class="fw-semibold text-decoration-none">{{ $fav->market->name }}</a>
                                <form method="POST" action="{{ route('favorites.toggle.market') }}">
                                    @csrf
                                    <input type="hidden" name="market_id" value="{{ $fav->market_id }}">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-heartbreak"></i></button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-12"><div class="ml-note"><i class="bi bi-info-circle"></i>No favorite markets yet.</div></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

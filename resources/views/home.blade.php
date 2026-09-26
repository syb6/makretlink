@extends('layouts.app')

@section('title', 'Fresh from local farms')

@section('content')
{{-- Hero --}}
<section class="marketlink-hero">
    <div class="container py-lg-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="hero-chip mb-3">🌱 THE EGREEN BASKET</span>
                <h1 class="display-5 fw-bold mb-3">Know what's at the market <em>before</em> you go.</h1>
                <p class="lead mb-4 opacity-90">MarketLink connects you with local farmers. See weekly stock and prices ahead of time, pre-order for pickup, and never miss out on your favourites again.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('products.index') }}" class="btn btn-light btn-lg fw-semibold px-4">Browse Products <i class="bi bi-arrow-right ms-1"></i></a>
                    <a href="{{ route('markets.index') }}" class="btn btn-outline-light btn-lg px-4">Find Markets</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="bg-white bg-opacity-10 border border-light border-opacity-25 rounded-4 p-3 text-center backdrop-blur">
                            <div class="fs-2 fw-bold">{{ $marketCount }}</div>
                            <div class="small text-uppercase opacity-75">Markets</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-white bg-opacity-10 border border-light border-opacity-25 rounded-4 p-3 text-center">
                            <div class="fs-2 fw-bold">{{ $farmerCount }}</div>
                            <div class="small text-uppercase opacity-75">Farmers</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-white bg-opacity-10 border border-light border-opacity-25 rounded-4 p-3 text-center">
                            <div class="fs-2 fw-bold">{{ $productCount }}</div>
                            <div class="small text-uppercase opacity-75">Products</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-white bg-opacity-10 border border-light border-opacity-25 rounded-4 p-3 text-center">
                            <div class="fs-2 fw-bold">7</div>
                            <div class="small text-uppercase opacity-75">Days a week</div>
                        </div>
                    </div>
                </div>
                @if ($announcements->isNotEmpty())
                    <div class="mt-4 bg-white bg-opacity-10 border border-light border-opacity-25 rounded-4 p-3">
                        <div class="fw-semibold mb-2"><i class="bi bi-megaphone me-2"></i>Announcements</div>
                        @foreach ($announcements as $a)
                            <div class="small mb-1"><span class="opacity-75">{{ $a->published_at?->format('M j') }} —</span> {{ $a->title }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- Categories --}}
<section class="py-5">
    <div class="container">
        <h2 class="section-title mb-4">Shop by <span class="accent">category</span></h2>
        <div class="row g-3">
            @forelse ($categories as $cat)
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ route('products.index', ['category' => $cat->id]) }}" class="text-decoration-none">
                        @php
                            $emojiMap = ['vegetables' => '🥬', 'fruits' => '🍎', 'dairy-eggs' => '🥛', 'baked-goods' => '🍞', 'herbs-greens' => '🌿', 'honey-preserves' => '🍯'];
                            $emoji = $emojiMap[$cat->slug] ?? collect(['🥕', '🥔', '🌽', '🫑', '🧄', '🍄'])[$cat->id % 6];
                        @endphp
                        <div class="card-ml text-center py-4 h-100">
                            <div class="fs-2 mb-2">{{ $emoji }}</div>
                            <div class="fw-semibold text-dark small">{{ $cat->name }}</div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12 text-muted">No categories yet.</div>
            @endforelse
        </div>
    </div>
</section>

{{-- Featured stock --}}
<section class="py-5 bg-ml-green-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="section-title mb-1">Fresh this <span class="accent">week</span></h2>
                <p class="text-muted mb-0">Live weekly stock from farmers across all markets.</p>
            </div>
            <a href="{{ route('products.index') }}" class="btn btn-outline-ml btn-sm fw-semibold">View all</a>
        </div>
        <div class="row g-4">
            @forelse ($featuredStocks as $stock)
                @include('products._card', ['stock' => $stock])
            @empty
                <div class="col-12">
                    <div class="alert alert-light border">No weekly stock has been published yet. Check back soon!</div>
                </div>
            @endforelse
        </div>
    </div>
</section>

{{-- How it works --}}
<section class="py-5">
    <div class="container">
        <h2 class="section-title text-center mb-5">How <span class="accent">MarketLink</span> works</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card-ml h-100 p-4 text-center">
                    <div class="fs-1 mb-2">🧺</div>
                    <h5 class="fw-bold">1. Browse & fill your basket</h5>
                    <p class="text-muted small mb-0">Search live weekly stock from every farmer stall, filtered by market, category, price and day.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-ml h-100 p-4 text-center">
                    <div class="fs-1 mb-2">📅</div>
                    <h5 class="fw-bold">2. Pick a pickup slot</h5>
                    <p class="text-muted small mb-0">Choose a date and time window offered by the farmer. Pre-order against real available stock.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-ml h-100 p-4 text-center">
                    <div class="fs-1 mb-2">🤝</div>
                    <h5 class="fw-bold">3. Pick up & pay in person</h5>
                    <p class="text-muted small mb-0">Collect at the market during your window and pay the farmer directly. Simple and secure.</p>
                </div>
            </div>
        </div>
    </div>
</section>

@include('chatbot.widget')
@endsection

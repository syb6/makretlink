@extends('layouts.app')

@section('title', 'Fresh From Local Farmers, Made Easy for You')

@section('content')
{{-- Hero --}}
<section class="hero-section" id="home">
    <div class="hero-bg-glow" aria-hidden="true"></div>
    <div class="hero-bg-glow-2" aria-hidden="true"></div>
    <div class="container hero-grid">
        <div class="hero-content">
            <span class="badge-sub">LOCAL &bull; FRESH &bull; COMMUNITY</span>
            <h1 class="hero-title">Fresh From Local Farmers, Made Easy for You.</h1>
            <p class="hero-description">Discover local farmers, explore fresh weekly produce, and reserve what you need online for convenient, hassle-free market pickup.</p>
            <div class="hero-btns">
                <a href="{{ route('markets.index') }}" class="btn btn-ml">Explore Markets</a>
                <a href="{{ route('products.index') }}" class="btn btn-outline-ml">Browse Products</a>
            </div>
            @if ($announcements->isNotEmpty())
                <div class="mt-4 p-3 rounded-4" style="background: var(--surface); border: 1px solid var(--border-color); max-width: 480px;">
                    <div class="fw-semibold mb-2 small"><i class="bi bi-megaphone me-2" style="color: var(--primary-dark)"></i>Announcements</div>
                    @foreach ($announcements as $a)
                        <div class="small mb-1"><span class="opacity-75">{{ $a->published_at?->format('M j') }} —</span> {{ $a->title }}</div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="hero-image-wrapper text-center">
            <picture>
                <source srcset="https://images.unsplash.com/photo-1610832958506-aa56368176cf?auto=format&fit=crop&w=800&q=70&fm=avif" type="image/avif">
                <img src="https://images.unsplash.com/photo-1610832958506-aa56368176cf?auto=format&fit=crop&w=800&q=80"
                     alt="Fresh organic vegetables" class="hero-img"
                     width="800" height="533" fetchpriority="high"
                     onerror="this.style.display='none'">
            </picture>
        </div>
    </div>
</section>

{{-- Blend the hero into the page background --}}
<hr class="section-divider-fade" style="background: linear-gradient(180deg, var(--light-bg), var(--cream));" aria-hidden="true">

{{-- Feature strip --}}
<section class="features-section">
    <div class="container">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon-circle"><i class="bi bi-flower2"></i></div>
                <div class="feature-info"><h4>Fresh Local Products</h4><p>Discover products directly from verified local farmers and markets.</p></div>
            </div>
            <div class="feature-card">
                <div class="feature-icon-circle"><i class="bi bi-cart-check"></i></div>
                <div class="feature-info"><h4>Easy Pre-Orders</h4><p>Reserve your seasonal produce online before market day sells out.</p></div>
            </div>
            <div class="feature-card">
                <div class="feature-icon-circle"><i class="bi bi-shop"></i></div>
                <div class="feature-info"><h4>Convenient Pickup</h4><p>Collect your items directly at the market stall and pay in person.</p></div>
            </div>
        </div>
    </div>
</section>

{{-- Categories --}}
<section class="section-padding">
    <div class="container">
        <div class="text-center">
            <span class="badge-sub">ORGANIC SELECTION</span>
            <h2 class="section-title">Explore Categories</h2>
            <p class="section-subtitle">Browse through our wide range of farm-fresh categories directly from community growers.</p>
        </div>
        <div class="categories-grid">
            @forelse ($categories as $cat)
                <a href="{{ route('products.index', ['category' => $cat->id]) }}" class="category-card">
                    <div class="category-img-circle">
                        @include('products._image', ['product' => null, 'categoryImage' => true, 'category' => $cat, 'class' => ''])
                    </div>
                    <h3 class="category-title">{{ $cat->name }}</h3>
                </a>
            @empty
                <div class="col-12 text-muted">No categories yet.</div>
            @endforelse
        </div>
    </div>
</section>

{{-- Markets --}}
<section class="section-padding" id="markets" style="background-color: var(--light-bg)">
    <div class="container">
        <div class="text-center">
            <span class="badge-sub">COMMUNITY HUBS</span>
            <h2 class="section-title">Explore Local Markets</h2>
            <p class="section-subtitle">Find participating weekly markets near you, check operating days, and pre-order fresh produce.</p>
        </div>
        <div class="markets-grid">
            @forelse ($markets as $market)
                @php
                    $firstOpen = $market->schedules->firstWhere('is_closed', false);
                @endphp
                <div class="market-card">
                    <div class="market-img-box">
                        <span class="market-status-tag">{{ $firstOpen ? 'Open '.\App\Models\MarketSchedule::DAYS[$firstOpen->day_of_week] : 'See schedule' }}</span>
                        <img src="{{ $market->image_url }}" alt="{{ $market->name }}"
                             width="600" height="400" loading="lazy"
                             onerror="this.onerror=null;this.src=this.dataset.fallback;"
                             data-fallback="{{ asset('images/placeholders/market.svg') }}">
                    </div>
                    <div class="market-details">
                        <h3 class="market-title">{{ $market->name }}</h3>
                        <div class="market-meta">
                            <span><i class="bi bi-geo-alt-fill"></i> {{ $market->address }}</span>
                            @if ($firstOpen)
                                <span><i class="bi bi-clock-fill"></i> {{ \App\Models\MarketSchedule::DAYS[$firstOpen->day_of_week] }}: {{ $firstOpen->opening_time?->format('g:i A') }} - {{ $firstOpen->closing_time?->format('g:i A') }}</span>
                            @endif
                        </div>
                        <p class="market-desc">{{ \Illuminate\Support\Str::limit($market->description ?? 'A community market with local growers and artisans.', 110) }}</p>
                        <div class="market-bottom">
                            <span class="vendor-count"><i class="bi bi-shop me-1"></i> {{ $market->farmers_count }} Local Farmer{{ $market->farmers_count === 1 ? '' : 's' }}</span>
                            <a href="{{ route('markets.show', $market) }}" class="btn-explore-market">View Market</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="alert alert-light border">No active markets yet. Check back soon!</div></div>
            @endforelse
        </div>
        <div class="text-center mt-4">
            <a href="{{ route('markets.index') }}" class="btn btn-outline-ml">View All Markets <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
    </div>
</section>

{{-- Leaf divider --}}
<div class="section-divider-leaf" aria-hidden="true"><i class="bi bi-flower1"></i></div>

{{-- Featured products --}}
<section class="section-padding" id="products">
    <div class="container">
        <div class="text-center">
            <span class="badge-sub">FRESH ARRIVALS</span>
            <h2 class="section-title">Farm Fresh Picks</h2>
            <p class="section-subtitle">A sample of what's currently listed by farmers across MarketLink this week.</p>
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
        <div class="text-center mt-4">
            <a href="{{ route('products.index') }}" class="btn btn-outline-ml">View All Products <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
    </div>
</section>

{{-- How it works --}}
<section class="section-padding" style="background-color: var(--light-bg)">
    <div class="container">
        <div class="text-center">
            <span class="badge-sub">SIMPLE PROCESS</span>
            <h2 class="section-title">How MarketLink Works</h2>
            <p class="section-subtitle">A seamless way to support local farmers and get guaranteed fresh market produce.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">01</div>
                <h3>Discover</h3>
                <p>Find nearby farmers markets and view profiles of local participating farmers.</p>
            </div>
            <div class="step-card">
                <div class="step-number">02</div>
                <h3>Choose</h3>
                <p>Browse weekly updated stock lists, check real-time availability, and reserve your items.</p>
            </div>
            <div class="step-card">
                <div class="step-number">03</div>
                <h3>Pickup</h3>
                <p>Collect your pre-ordered goods at the market stall and pay the farmer directly.</p>
            </div>
        </div>
    </div>
</section>

<hr class="section-divider-fade fade-to-primary" aria-hidden="true">

{{-- Promo banner --}}
<section class="promo-banner-section">
    <i class="bi bi-flower1 promo-bg-pattern"></i>
    <div class="container promo-flex">
        <div class="promo-content">
            <h2>Fresh Choices. Local Farmers.</h2>
            <p>Never arrive to sold-out market stalls again. Reserve weekly seasonal yields directly from local family farms before market day.</p>
            <a href="{{ route('markets.index') }}" class="btn btn-white">Explore Markets Nearby</a>
        </div>
        <div class="promo-img-box d-none d-md-block">
            <picture>
                <source srcset="https://images.unsplash.com/photo-1615485290382-441e4d049cb5?auto=format&fit=crop&w=600&q=70&fm=avif" type="image/avif">
                <img src="https://images.unsplash.com/photo-1615485290382-441e4d049cb5?auto=format&fit=crop&w=600&q=80" alt="Fresh vegetable basket" width="600" height="600" loading="lazy" onerror="this.style.display='none'">
            </picture>
        </div>
    </div>
</section>

<hr class="section-divider-fade fade-from-primary" aria-hidden="true">

{{-- CTA --}}
<section class="cta-section" id="contact">
    <div class="container cta-box">
        <h2>Ready to Discover Your Local Market?</h2>
        <p>Explore fresh seasonal produce, support local farming families, and reserve your next market pickup effortlessly — or list your own harvest as a farmer.</p>
        <div class="cta-btns">
            <a href="{{ route('register') }}" class="btn btn-ml">Sign Up as a Customer</a>
            <a href="{{ route('register', ['role' => 'farmer']) }}" class="btn btn-outline-ml">Register as a Farmer</a>
        </div>
    </div>
</section>

@endsection

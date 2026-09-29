@extends('layouts.app')

@section('title', 'Fresh From Local Farmers, Made Easy for You')

@section('content')
{{-- Hero --}}
<section class="hero-section" id="home">
    {{-- Full-bleed background photo: slow Ken Burns zoom, themed scrim on top --}}
    <div class="hero-bg-photo" aria-hidden="true">
        <img src="{{ asset('images/hero-bg.webp') }}"
             srcset="{{ asset('images/hero-bg-800.webp') }} 800w, {{ asset('images/hero-bg.webp') }} 1600w"
             sizes="100vw"
             alt="" width="1600" height="900" fetchpriority="high" decoding="async"
             onload="this.classList.add('is-loaded');">
    </div>
    <div class="hero-bg-scrim" aria-hidden="true"></div>
    <div class="hero-bg-glow" aria-hidden="true"></div>
    <div class="hero-bg-glow-2" aria-hidden="true"></div>
    <div class="container hero-grid">
        {{-- Staggered entrance: pure CSS, no JS dependency (ml-in + --d delay) --}}
        <div class="hero-content">
            <span class="badge-sub ml-in" style="--d: 60ms">LOCAL &bull; FRESH &bull; COMMUNITY</span>
            {{-- Title words animate in one by one (--i drives the stagger); the
                 accent words get a slow gradient shine after they land. --}}
            <h1 class="hero-title" aria-label="Fresh From Local Farmers, Made Easy for You.">
                @php
                    $heroWords = [
                        ['Fresh', false], ['From', false], ['Local', true], ['Farmers,', false],
                        ['Made', false], ['Easy', true], ['for', false], ['You.', false],
                    ];
                @endphp
                @foreach ($heroWords as $i => [$word, $isAccent])
                    <span class="w {{ $isAccent ? 'accent' : '' }}" style="--i: {{ $i }}" aria-hidden="true">{{ $word }}</span>{{ ' ' }}
                @endforeach
            </h1>
            <p class="hero-description ml-in" style="--d: 280ms">Discover local farmers, explore fresh weekly produce, and reserve what you need online for convenient, hassle-free market pickup.</p>
            <div class="hero-btns ml-in" style="--d: 400ms">
                <a href="{{ route('markets.index') }}" class="btn btn-ml btn-lg-ml">Explore Markets <i class="bi bi-arrow-right"></i></a>
                <a href="{{ route('products.index') }}" class="btn btn-outline-ml btn-lg-ml">Browse Products</a>
            </div>
            {{-- Live platform stats — count up when scrolled into view --}}
            <div class="hero-stats ml-in" style="--d: 540ms" role="list" aria-label="MarketLink in numbers">
                <div class="hero-stat" role="listitem">
                    <span class="hero-stat-number stat-number" data-suffix="+">{{ $marketCount }}</span>
                    <span class="hero-stat-label">Active Markets</span>
                </div>
                <div class="hero-stat" role="listitem">
                    <span class="hero-stat-number stat-number" data-suffix="+">{{ $farmerCount }}</span>
                    <span class="hero-stat-label">Local Farmers</span>
                </div>
                <div class="hero-stat" role="listitem">
                    <span class="hero-stat-number stat-number" data-suffix="+">{{ $productCount }}</span>
                    <span class="hero-stat-label">Fresh Products</span>
                </div>
            </div>
            @if ($announcements->isNotEmpty())
                <div class="hero-announcements ml-in" style="--d: 660ms">
                    <div class="hero-announcements-head">
                        <i class="bi bi-megaphone-fill"></i><span>Latest Announcements</span>
                    </div>
                    <div class="hero-announcements-body">
                        @foreach ($announcements as $a)
                            <div class="hero-announcement">
                                <span class="hero-announcement-date">{{ $a->published_at?->format('M j') }}</span>
                                <span class="hero-announcement-text">{{ $a->title }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        <div class="hero-image-wrapper text-center ml-in" style="--d: 300ms">
            <picture>
                <img src="{{ asset('images/hero-basket.webp') }}"
                     alt="Fresh organic vegetables" class="hero-img"
                     width="800" height="533" fetchpriority="high"
                     onload="this.classList.add('is-loaded');"
                     onerror="this.style.display='none'">
            </picture>
        </div>
    </div>
    {{-- Floating trust chips (pure decoration). Desktop: absolutely
         positioned over the hero photo. Stacked layouts: an in-flow
         pill strip under the photo so they never overlap the text. --}}
    <div class="hero-chips-strip" role="presentation">
        <span class="hero-float-chips chip-1" aria-hidden="true"><i class="bi bi-basket2-fill"></i> 1,200+ baskets reserved</span>
        <span class="hero-float-chips chip-2" aria-hidden="true"><i class="bi bi-star-fill"></i> 9 farms near you</span>
        <span class="hero-float-chips chip-3" aria-hidden="true"><i class="bi bi-patch-check-fill"></i> 100% verified farmers</span>
    </div>
    <a class="hero-scroll-cue" href="#features" aria-label="Scroll to features"><span>Scroll</span><i class="bi bi-chevron-double-down"></i></a>
</section>

{{-- Cross-fade the hero photo into the page background --}}
<div class="hero-blend" aria-hidden="true"></div>

{{-- Feature strip --}}
<section class="features-section" id="features">
    <div class="container">
        <div class="features-grid">
            <div class="feature-card reveal">
                <div class="feature-icon-circle"><i class="bi bi-flower2"></i></div>
                <div class="feature-info"><h4>Fresh Local Products</h4><p>Discover products directly from verified local farmers and markets.</p></div>
            </div>
            <div class="feature-card reveal" style="--d: 90ms">
                <div class="feature-icon-circle"><i class="bi bi-cart-check"></i></div>
                <div class="feature-info"><h4>Easy Pre-Orders</h4><p>Reserve your seasonal produce online before market day sells out.</p></div>
            </div>
            <div class="feature-card reveal" style="--d: 180ms">
                <div class="feature-icon-circle"><i class="bi bi-shop"></i></div>
                <div class="feature-info"><h4>Convenient Pickup</h4><p>Collect your items directly at the market stall and pay in person.</p></div>
            </div>
        </div>
    </div>
</section>

{{-- Categories --}}
<section class="section-padding">
    <div class="container">
        <div class="section-head">
            <span class="badge-sub">ORGANIC SELECTION</span>
            <h2 class="section-title">Explore <span class="accent">Categories</span></h2>
            <p class="section-subtitle">Browse through our wide range of farm-fresh categories directly from community growers.</p>
        </div>
        <div class="categories-grid">
            @forelse ($categories as $cat)
                <a href="{{ route('products.index', ['category' => $cat->id]) }}" class="category-card reveal">
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
        <div class="section-head">
            <span class="badge-sub">COMMUNITY HUBS</span>
            <h2 class="section-title">Explore <span class="accent">Local Markets</span></h2>
            <p class="section-subtitle">Find participating weekly markets near you, check operating days, and pre-order fresh produce.</p>
        </div>
        <div class="markets-grid">
            @forelse ($markets as $market)
                @php
                    $openDays = $market->openDays();
                    $firstOpen = $openDays->first();
                @endphp
                <div class="market-card reveal">
                    <div class="market-img-box">
                        <span class="market-status-tag">{{ $market->openDaysLabel() }}</span>
                        <img src="{{ $market->image_url }}" alt="{{ $market->name }}" loading="lazy"
                             width="600" height="400" loading="lazy"
                             onerror="this.onerror=null;this.src=this.dataset.fallback;"
                             data-fallback="{{ asset('images/placeholders/market.png') }}">
                    </div>
                    <div class="market-details">
                        <h3 class="market-title">{{ $market->name }}</h3>
                        <div class="market-meta">
                            <span><i class="bi bi-geo-alt-fill"></i> {{ $market->address }}</span>
                            @if ($firstOpen)
                                <span><i class="bi bi-clock-fill"></i> {{ \App\Models\MarketSchedule::DAYS[$firstOpen->day_of_week] }}: {{ $firstOpen->opening_time?->format('g:i A') }} - {{ $firstOpen->closing_time?->format('g:i A') }}</span>
                            @endif
                        </div>
                        @if ($openDays->count() > 1)
                            <p class="market-desc mb-2">
                                <span class="multi-market-note"><i class="bi bi-calendar-week me-1"></i>Trades {{ $openDays->count() }} days weekly — {{ $openDays->map(fn ($s) => \App\Models\MarketSchedule::DAYS[$s->day_of_week])->implode(' & ') }}</span>
                            </p>
                        @endif
                        <p class="market-desc">{{ \Illuminate\Support\Str::limit($market->description ?? 'A community market with local growers and artisans.', 110) }}</p>
                        <div class="market-bottom">
                            <span class="vendor-count"><i class="bi bi-shop me-1"></i> {{ $market->farmers_count }} Local Farmer{{ $market->farmers_count === 1 ? '' : 's' }}</span>
                            <a href="{{ route('markets.show', $market) }}" class="btn-explore-market">View Market</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="ml-note"><i class="bi bi-info-circle"></i>No active markets yet. Check back soon!</div></div>
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
        <div class="section-head">
            <span class="badge-sub">FRESH ARRIVALS</span>
            <h2 class="section-title">Farm <span class="accent">Fresh Picks</span></h2>
            <p class="section-subtitle">A sample of what's currently listed by farmers across MarketLink this week.</p>
        </div>
        <div class="row g-4">
            @forelse ($featuredStocks as $stock)
                @include('products._card', ['stock' => $stock])
            @empty
                <div class="col-12">
                    <div class="ml-note"><i class="bi bi-info-circle"></i>No weekly stock has been published yet. Check back soon!</div>
                </div>
            @endforelse
        </div>
        <div class="text-center mt-4">
            <a href="{{ route('products.index') }}" class="btn btn-outline-ml">View All Products <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
    </div>
</section>

{{-- How it works --}}
<section class="section-padding hiw-section" style="background-color: var(--light-bg)">
    <div class="container">
        <div class="section-head">
            <span class="badge-sub">SIMPLE PROCESS</span>
            <h2 class="section-title">How <span class="accent">MarketLink</span> Works</h2>
            <p class="section-subtitle">A seamless way to support local farmers and get guaranteed fresh market produce.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card reveal">
                <div class="step-number">01</div>
                <h3>Discover</h3>
                <p>Find nearby farmers markets and view profiles of local participating farmers.</p>
            </div>
            <div class="step-card reveal" style="--d: 120ms">
                <div class="step-number">02</div>
                <h3>Choose</h3>
                <p>Browse weekly updated stock lists, check real-time availability, and reserve your items.</p>
            </div>
            <div class="step-card reveal" style="--d: 240ms">
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
        <div class="promo-content reveal">
            <h2>Fresh Choices. Local Farmers.</h2>
            <p>Never arrive to sold-out market stalls again. Reserve weekly seasonal yields directly from local family farms before market day.</p>
            <a href="{{ route('markets.index') }}" class="btn btn-white">Explore Markets Nearby</a>
        </div>
        <div class="promo-img-box d-none d-md-block reveal reveal-right">
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
        <h2 class="reveal">Ready to Discover Your Local Market?</h2>
        <p class="reveal" style="--d: 90ms">Explore fresh seasonal produce, support local farming families, and reserve your next market pickup effortlessly — or list your own harvest as a farmer.</p>
        <div class="cta-btns reveal" style="--d: 180ms">
            <a href="{{ route('register') }}" class="btn btn-ml">Sign Up as a Customer</a>
            <a href="{{ route('register', ['role' => 'farmer']) }}" class="btn btn-outline-ml">Register as a Farmer</a>
        </div>
    </div>
</section>

@endsection

@extends('layouts.app')

@section('title', 'About Us')

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge-sub">OUR MISSION</span>
            <h1 class="section-title display-6">Fresh. Local. <span class="accent">Predictable.</span></h1>
        </div>

        <div class="about-grid mb-5">
            <div class="about-content">
                <h4 class="fw-bold">Why MarketLink exists</h4>
                <p>Local farmers markets are booming, but customers rarely know which farmers will be at the market on a given day, what stock they have, or at what price. Availability is shared on chalkboards, flyers and word of mouth — so shoppers arrive to find their favourite items sold out.</p>
                <p>MarketLink brings farmers and customers onto a single platform: farmers publish weekly stock and pricing, and customers browse, pre-order, and pick up at the market. Fewer wasted trips, better planning, stronger communities.</p>
                <div class="about-mini-grid">
                    <div class="about-mini-card"><i class="bi bi-basket"></i><span>Fresh &amp; Local</span></div>
                    <div class="about-mini-card"><i class="bi bi-clock-history"></i><span>Convenience</span></div>
                    <div class="about-mini-card"><i class="bi bi-people"></i><span>Community</span></div>
                </div>
            </div>
            <div class="about-img-box">
                <img src="https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=800&q=80" alt="Farmers market stall" loading="lazy" onerror="this.style.display='none'">
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-3 col-6"><div class="card-ml p-4 text-center h-100"><div class="fs-1">🌱</div><h6 class="fw-bold mt-2">Local produce</h6><p class="small text-muted mb-0">Seasonal, fresh, and grown nearby.</p></div></div>
            <div class="col-md-3 col-6"><div class="card-ml p-4 text-center h-100"><div class="fs-1">🧺</div><h6 class="fw-bold mt-2">Pre-order</h6><p class="small text-muted mb-0">Reserve before market day, skip the disappointment.</p></div></div>
            <div class="col-md-3 col-6"><div class="card-ml p-4 text-center h-100"><div class="fs-1">🤝</div><h6 class="fw-bold mt-2">Fair for farmers</h6><p class="small text-muted mb-0">Farmers plan harvests against real demand.</p></div></div>
            <div class="col-md-3 col-6"><div class="card-ml p-4 text-center h-100"><div class="fs-1">📍</div><h6 class="fw-bold mt-2">Easy pickup</h6><p class="small text-muted mb-0">Maps, stall locations, and clear pickup windows.</p></div></div>
        </div>

        <div class="cta-section rounded-4">
            <div class="cta-box">
                <h2 class="h3">Ready to taste the difference?</h2>
                <p>Join as a customer to pre-order, or as a farmer to start selling.</p>
                <div class="cta-btns">
                    <a href="{{ route('register') }}" class="btn btn-ml px-4">Join as Customer</a>
                    <a href="{{ route('register', ['role' => 'farmer']) }}" class="btn btn-outline-ml px-4">Join as Farmer</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

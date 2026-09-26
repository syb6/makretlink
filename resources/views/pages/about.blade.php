@extends('layouts.app')

@section('title', 'About Us')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="hero-chip bg-ml-green-light text-success border-0">ABOUT MARKETLINK</span>
            <h1 class="section-title display-6 mt-3">Fresh. Local. <span class="accent">Predictable.</span></h1>
        </div>

        <div class="row g-5 align-items-center mb-5">
            <div class="col-lg-6">
                <h4 class="fw-bold">Why MarketLink exists</h4>
                <p class="text-muted">Local farmers markets are booming, but customers rarely know which farmers will be at the market on a given day, what stock they have, or at what price. Availability is shared on chalkboards, flyers and word of mouth — so shoppers arrive to find their favourite items sold out.</p>
                <p class="text-muted">MarketLink brings farmers and customers onto a single platform: farmers publish weekly stock and pricing, and customers browse, pre-order, and pick up at the market. Fewer wasted trips, better planning, stronger communities.</p>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6"><div class="card-ml p-4 text-center"><div class="fs-1">🌱</div><h6 class="fw-bold mt-2">Local produce</h6><p class="small text-muted mb-0">Seasonal, fresh, and grown nearby.</p></div></div>
                    <div class="col-6"><div class="card-ml p-4 text-center"><div class="fs-1">🧺</div><h6 class="fw-bold mt-2">Pre-order</h6><p class="small text-muted mb-0">Reserve before market day, skip the disappointment.</p></div></div>
                    <div class="col-6"><div class="card-ml p-4 text-center"><div class="fs-1">🤝</div><h6 class="fw-bold mt-2">Fair for farmers</h6><p class="small text-muted mb-0">Farmers plan harvests against real demand.</p></div></div>
                    <div class="col-6"><div class="card-ml p-4 text-center"><div class="fs-1">📍</div><h6 class="fw-bold mt-2">Easy pickup</h6><p class="small text-muted mb-0">Maps, stall locations, and clear pickup windows.</p></div></div>
                </div>
            </div>
        </div>

        <div class="bg-ml-green-light rounded-4 p-5 text-center">
            <h4 class="fw-bold mb-2">Ready to taste the difference?</h4>
            <p class="text-muted">Join as a customer to pre-order, or as a farmer to start selling.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="{{ route('register') }}" class="btn btn-ml px-4">Join as Customer</a>
                <a href="{{ route('register', ['role' => 'farmer']) }}" class="btn btn-outline-ml px-4">Join as Farmer</a>
            </div>
        </div>
    </div>
</section>
@endsection

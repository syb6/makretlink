@extends('layouts.app')

@section('title', 'Farmers')

@section('content')
<section class="page-banner anim-up">
    <i class="bi bi-flower2 page-banner-icon" aria-hidden="true"></i>
    <div class="container">
        <span class="badge-sub">MEET THE GROWERS</span>
        <h1 class="section-title mb-1">Our Farmers</h1>
        <p class="section-subtitle mb-0">Meet the people growing your food.</p>
    </div>
</section>
<section class="py-4 py-md-5">
    <div class="container">

        <form method="GET" class="d-flex gap-2 flex-wrap justify-content-center mb-4">
            <input type="text" name="q" class="form-control" style="max-width:220px" placeholder="Search farmers..." value="{{ $search }}">
            <select name="market" class="form-select" style="max-width:200px">
                <option value="">All markets</option>
                @foreach ($markets as $m)
                    <option value="{{ $m->id }}" {{ $selectedMarket == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-ml">Filter</button>
        </form>

        <div class="row g-4">
            @forelse ($farmers as $farmer)
                <div class="col-sm-6 col-lg-4">
                    <div class="card-ml h-100 p-4 d-flex flex-column position-relative">                            @auth
                                @if (auth()->user()->isCustomer())
                                    @php
                                        // Set passed from the controller — no per-card query.
                                        $isFavFarmer = isset($favoriteFarmerIds) && $favoriteFarmerIds->contains($farmer->id);
                                    @endphp
                                    <button type="button" class="fav-btn favorite-farmer-btn"
                                        data-url="{{ route('favorites.toggle.farmer') }}"
                                        data-farmer-id="{{ $farmer->id }}"
                                        title="{{ $isFavFarmer ? 'Remove from favorites' : 'Save to favorites' }}"
                                        aria-label="Toggle favorite farmer">
                                    <i class="bi {{ $isFavFarmer ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                                </button>
                            @endif
                        @endauth
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="brand-leaf">{{ strtoupper(substr($farmer->business_name, 0, 1)) }}</span>
                            <div>
                                <h5 class="fw-bold mb-0">{{ $farmer->business_name }}</h5>
                                <small class="text-muted"><i class="bi bi-person me-1"></i>{{ $farmer->contact_person }}</small>
                            </div>
                        </div>
                        <p class="small text-muted mb-3">
                            {{ \Illuminate\Support\Str::limit($farmer->description ?: $farmer->address, 110) }}
                        </p>
                        <div class="mb-3">
                            @foreach ($farmer->farmerMarkets as $fm)
                                <span class="badge badge-soft mb-1"><i class="bi bi-geo-alt me-1"></i>{{ $fm->market->name }}</span>
                            @endforeach
                        </div>
                        <div class="mt-auto">
                            <a href="{{ route('farmers.show', $farmer) }}" class="btn btn-outline-ml btn-sm w-100">View profile &amp; stock</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="ml-note"><i class="bi bi-info-circle"></i>No farmers found. Try different filters.</div></div>
            @endforelse
        </div>

        <div class="mt-4">{{ $farmers->links() }}</div>
    </div>
</section>

@endsection

@extends('layouts.app')

@section('title', $farmer->business_name)

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ route('farmers.index') }}">Farmers</a></li>
                <li class="breadcrumb-item active">{{ $farmer->business_name }}</li>
            </ol>
        </nav>

        <div class="card-ml p-4 mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="brand-leaf" style="width:60px;height:60px;font-size:1.7rem;border-radius:16px;">
                            {{ strtoupper(substr($farmer->business_name, 0, 1)) }}
                        </span>
                        <div>
                            <h1 class="section-title mb-0">{{ $farmer->business_name }}</h1>
                            <span class="status-pill status-approved">Approved farmer</span>
                        </div>
                    </div>
                    <p class="text-muted mb-1"><i class="bi bi-person me-1"></i>{{ $farmer->contact_person }} · {{ $farmer->user->email }}</p>
                    <p class="text-muted mb-1"><i class="bi bi-geo-alt me-1"></i>{{ $farmer->address }}</p>
                    @if ($farmer->description)
                        <p class="mb-0">{{ $farmer->description }}</p>
                    @endif
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold mb-2">Operating at</h6>
                    @auth
                        @if (auth()->user()->isCustomer())
                            @php
                                // Set passed from the controller — no extra query.
                                $isFavFarmer = auth()->user()->customerProfile
                                    && isset($favoriteFarmerIds) && $favoriteFarmerIds->contains($farmer->id);
                            @endphp
                            <button type="button"
                                    class="btn btn-sm {{ $isFavFarmer ? 'btn-ml' : 'btn-outline-ml' }} mb-3 favorite-farmer-btn"
                                    data-url="{{ route('favorites.toggle.farmer') }}"
                                    data-farmer-id="{{ $farmer->id }}"
                                    aria-pressed="{{ $isFavFarmer ? 'true' : 'false' }}">
                                <i class="bi {{ $isFavFarmer ? 'bi-heart-fill' : 'bi-heart' }} me-1"></i>
                                {{ $isFavFarmer ? 'Saved to favorites' : 'Save to favorites' }}
                            </button>
                        @endif
                    @endauth
                    @foreach ($farmer->farmerMarkets as $fm)
                        <div class="mb-2">
                            <a href="{{ route('markets.show', $fm->market) }}" class="fw-semibold text-decoration-none">{{ $fm->market->name }}</a>
                            <div class="small text-muted">
                                @foreach ($fm->schedules as $s)
                                    {{ \App\Models\MarketSchedule::DAYS[$s->day_of_week] }} {{ $s->start_time->format('gA') }}–{{ $s->end_time->format('gA') }}@if(!$loop->last), @endif
                                @endforeach
                                @if ($fm->stall_location)
                                    · {{ $fm->stall_location }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="text-center mb-4">
            <span class="badge-sub">WEEKLY AVAILABILITY</span>
            <h2 class="section-title h3">Current weekly stock</h2>
        </div>
        <div class="row g-4">
            @forelse ($stocks as $stock)
                @include('products._card', ['stock' => $stock])
            @empty
                <div class="col-12"><div class="alert alert-light border">This farmer has not published weekly stock yet.</div></div>
            @endforelse
        </div>
    </div>
</section>

@endsection

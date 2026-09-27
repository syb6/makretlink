@extends('layouts.app')

@section('title', $market->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ route('markets.index') }}">Markets</a></li>
                <li class="breadcrumb-item active">{{ $market->name }}</li>
            </ol>
        </nav>

        <div class="row g-4">
            <div class="col-lg-7">
                <span class="badge-sub">LOCAL MARKET</span>
                <h1 class="section-title mb-2 d-flex align-items-center flex-wrap gap-2">
                    {{ $market->name }}
                    @auth
                        @if (auth()->user()->isCustomer())
                            @php
                                $isFavMarket = \App\Models\FavoriteMarket::where('customer_id', auth()->user()->customerProfile->id)
                                    ->where('market_id', $market->id)->exists();
                            @endphp
                            <button type="button"
                                    class="btn btn-sm {{ $isFavMarket ? 'btn-ml' : 'btn-outline-ml' }} favorite-market-btn"
                                    data-url="{{ route('favorites.toggle.market') }}"
                                    data-market-id="{{ $market->id }}"
                                    aria-pressed="{{ $isFavMarket ? 'true' : 'false' }}">
                                <i class="bi {{ $isFavMarket ? 'bi-heart-fill' : 'bi-heart' }} me-1"></i>
                                {{ $isFavMarket ? 'Saved' : 'Save' }}
                            </button>
                        @endif
                    @endauth
                </h1>
                <p class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $market->address }}</p>
                @if ($market->description)
                    <p>{{ $market->description }}</p>
                @endif

                <h5 class="fw-bold mt-4 mb-3">Weekly schedule</h5>
                <div class="row g-2">
                    @foreach ($market->schedules->sortBy('day_of_week') as $s)
                        <div class="col-6 col-md-4">
                            <div class="card-ml p-2 text-center {{ $s->is_closed ? 'opacity-50' : '' }}">
                                <div class="fw-semibold small">{{ \App\Models\MarketSchedule::DAYS[$s->day_of_week] }}</div>
                                <div class="small text-muted">
                                    {{ $s->is_closed ? 'Closed' : $s->opening_time?->format('g:i A') . ' – ' . $s->closing_time?->format('g:i A') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <h5 class="fw-bold mt-5 mb-3">Farmers at this market ({{ $stalls->count() }})</h5>
                <div class="row g-3">
                    @forelse ($stalls as $stall)
                        <div class="col-md-6">
                            <div class="card-ml p-3 h-100">
                                <div class="d-flex justify-content-between mb-1 gap-2">
                                    <a href="{{ route('farmers.show', $stall->farmer) }}" class="fw-semibold text-decoration-none">{{ $stall->stall_name ?? $stall->farmer->business_name }}</a>
                                    <span class="status-pill status-{{ $stall->status }}">Active</span>
                                </div>
                                <div class="small text-muted mb-2">
                                    <i class="bi bi-person me-1"></i>{{ $stall->farmer->contact_person }}
                                    @if ($stall->stall_location)
                                        · <i class="bi bi-pin-map me-1"></i>{{ $stall->stall_location }}
                                    @endif
                                </div>
                                <div class="small">
                                    @foreach ($stall->schedules->sortBy('day_of_week') as $fs)
                                        <span class="badge badge-soft me-1 mb-1">{{ \App\Models\MarketSchedule::DAYS[$fs->day_of_week] }} {{ $fs->start_time->format('gA') }}–{{ $fs->end_time->format('gA') }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12"><div class="alert alert-light border">No farmers registered at this market yet.</div></div>
                    @endforelse
                </div>
            </div>

            <div class="col-lg-5">
                <div id="map"></div>
                <div class="card-ml p-4 mt-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-star me-2" style="color: var(--primary-dark)"></i>Featured products here</h6>
                    <div class="d-grid gap-2">
                        @forelse ($featuredProducts as $p)
                            <a href="{{ route('products.show', $p) }}" class="d-flex justify-content-between align-items-center text-decoration-none border-bottom pb-2">
                                <span>{{ $p->name }} <small class="text-muted">· {{ $p->farmer->business_name }}</small></span>
                                <span class="product-price">Rs {{ number_format($p->price, 2) }}</span>
                            </a>
                        @empty
                            <span class="text-muted small">Nothing in stock right now.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>if (typeof L === 'undefined') document.write('<script src="https:\/\/unpkg.com\/leaflet@1.9.4\/dist\/leaflet.js"><\/script>');</script>
<script>
    (function () {
        @if ($market->latitude && $market->longitude)
            var lat = {{ $market->latitude }}, lng = {{ $market->longitude }};
        @else
            var lat = 40.7128, lng = -74.006;
        @endif
        var map = L.map('map').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        L.marker([lat, lng]).addTo(map)
            .bindPopup('<strong>{{ $market->name }}</strong><br>{{ $market->address }}')
            .openPopup();
    })();
</script>
@endpush
@endsection

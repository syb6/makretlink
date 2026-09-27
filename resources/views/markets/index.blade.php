@extends('layouts.app')

@section('title', 'Markets')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge-sub">COMMUNITY HUBS</span>
            <h1 class="section-title">Farmers Markets</h1>
            <p class="section-subtitle mb-4">Find a market near you and see who is trading there.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <form method="GET" class="card card-ml p-3 mb-4">
                    <label class="form-label fw-semibold small">Filter by operating day</label>
                    <select name="day" class="form-select mb-3">
                        <option value="">Any day</option>
                        @foreach ($days as $i => $label)
                            <option value="{{ $i }}" {{ $selectedDay === (string) $i ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="d-flex gap-2">
                        <button class="btn btn-ml btn-sm flex-grow-1">Apply</button>
                        <a href="{{ route('markets.index') }}" class="btn btn-outline-ml btn-sm">Reset</a>
                    </div>
                </form>

                <div id="map" class="mb-2"></div>
                <p class="small text-muted"><i class="bi bi-info-circle me-1"></i>Click a marker for details &amp; directions (OpenStreetMap).</p>
            </div>

            <div class="col-lg-8">
                <div class="row g-4">
                    @forelse ($markets as $market)
                        <div class="col-md-6">
                            <div class="market-card h-100">
                                <div class="market-img-box">
                                    <span class="market-status-tag {{ $market->status === 'active' ? '' : 'inactive' }}">Open</span>
                                    <img src="{{ $market->image_url }}" alt="{{ $market->name }}"
                                         width="600" height="400" loading="lazy"
                                         onerror="this.onerror=null;this.src=this.dataset.fallback;"
                                         data-fallback="{{ asset('images/placeholders/market.svg') }}">
                                </div>
                                <div class="market-details">
                                    <h3 class="market-title">{{ $market->name }}</h3>
                                    <div class="market-meta">
                                        <span><i class="bi bi-geo-alt-fill"></i>{{ $market->address }}</span>
                                    </div>
                                    <div class="mb-3">
                                        @foreach ($market->schedules->sortBy('day_of_week') as $s)
                                            <span class="badge badge-soft me-1 mb-1 {{ $s->is_closed ? 'opacity-50 text-decoration-line-through' : '' }}">
                                                {{ \App\Models\MarketSchedule::DAYS[$s->day_of_week] }}
                                                @unless ($s->is_closed)
                                                    {{ $s->opening_time?->format('gA') }}–{{ $s->closing_time?->format('gA') }}
                                                @endunless
                                            </span>
                                        @endforeach
                                    </div>
                                    <div class="market-bottom">
                                        <span class="vendor-count"><i class="bi bi-people me-1"></i>{{ $market->farmers_count }} farmer{{ $market->farmers_count === 1 ? '' : 's' }}</span>
                                        <a href="{{ route('markets.show', $market) }}" class="btn-explore-market">View Market</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-light border">No markets match that day. Try another filter.</div>
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">{{ $markets->links() }}</div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    (function () {
        var map = L.map('map').setView([40.7128, -74.006], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        fetch("{{ route('markets.map') }}")
            .then(function (r) { return r.json(); })
            .then(function (geo) {
                var layer = L.geoJSON(geo, {
                    onEachFeature: function (f, layer) {
                        var p = f.properties;
                        layer.bindPopup(
                            '<strong>' + p.name + '</strong><br>' + p.address +
                            '<br>' + p.farmers + ' farmer(s)' +
                            '<br><a href="' + p.url + '">View market</a>' +
                            '<br><a href="https://www.google.com/maps/dir/?api=1&destination=' + f.geometry.coordinates[1] + ',' + f.geometry.coordinates[0] + '" target="_blank" rel="noopener">Get directions ↗</a>'
                        );
                    }
                }).addTo(map);
                try { map.fitBounds(layer.getBounds().pad(0.25)); } catch (e) {}
            });
    })();
</script>
@endpush
@endsection

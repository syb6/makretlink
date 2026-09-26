@extends('layouts.app')

@section('title', 'Markets')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h1 class="section-title mb-1">Farmers <span class="accent">Markets</span></h1>
                <p class="text-muted">Find a market near you and see who is trading there.</p>

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
                        <a href="{{ route('markets.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </form>

                <div id="map" class="mb-2"></div>
                <p class="small text-muted"><i class="bi bi-info-circle me-1"></i>Click a marker for details & directions (OpenStreetMap).</p>
            </div>

            <div class="col-lg-8">
                <div class="row g-4">
                    @forelse ($markets as $market)
                        <div class="col-md-6">
                            <div class="card-ml h-100 p-4 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="fw-bold mb-0">{{ $market->name }}</h5>
                                    <span class="status-pill status-{{ $market->status }}">Open</span>
                                </div>
                                <p class="small text-muted mb-2"><i class="bi bi-geo-alt me-1"></i>{{ $market->address }}</p>
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
                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <span class="small text-muted"><i class="bi bi-people me-1"></i>{{ $market->farmers_count }} farmer{{ $market->farmers_count === 1 ? '' : 's' }}</span>
                                    <a href="{{ route('markets.show', $market) }}" class="btn btn-outline-ml btn-sm">View market <i class="bi bi-arrow-right ms-1"></i></a>
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

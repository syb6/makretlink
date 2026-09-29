@extends('layouts.app')

@section('title', 'Markets')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('content')
<section class="page-banner anim-up">
    <i class="bi bi-geo-alt-fill page-banner-icon" aria-hidden="true"></i>
    <div class="container">
        <span class="badge-sub">COMMUNITY HUBS</span>
        <h1 class="section-title">Farmers Markets</h1>
        <p class="section-subtitle mb-0">Find a market near you and see who is trading there.</p>
    </div>
</section>
<section class="py-4 py-md-5">
    <div class="container">

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

                <div class="card card-ml p-3 mb-4">
                    <label class="form-label fw-semibold small"><i class="bi bi-geo-alt-fill me-1" style="color: var(--primary-dark)"></i>Markets near you</label>
                    <p class="small text-muted mb-3">Use your device location to find active markets within 10 km.</p>
                    <button id="findNearbyBtn" type="button" class="btn btn-ml w-100">
                        <i class="bi bi-crosshair2 me-1"></i> Find Markets Near Me
                    </button>
                    <div id="nearbyStatus" class="small mt-2 d-none" role="status" aria-live="polite"></div>
                </div>

                <div id="map" class="mb-2"></div>
                <p class="small text-muted"><i class="bi bi-info-circle me-1"></i>Click a marker for details &amp; directions (OpenStreetMap).</p>

                <div id="nearbyResults" class="d-none mt-3 anim-up">
                    <div class="card-ml p-3 mb-3">
                        <div class="fw-semibold small mb-1"><i class="bi bi-geo me-1" style="color: var(--primary-dark)"></i>Your location</div>
                        <div id="nearbyAddress" class="small text-muted" style="word-break: break-word"></div>
                    </div>
                    <h6 class="fw-semibold small text-uppercase text-muted mb-2" id="nearbyCount"></h6>
                    <div id="nearbyMarkets" class="d-flex flex-column gap-2"></div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="row g-4">
                    @forelse ($markets as $market)
                        <div class="col-md-6">
                            <div class="market-card h-100 position-relative">
                                <div class="market-img-box">
                                    @auth
                                        @if (auth()->user()->isCustomer())
                                            @php
                                                // Set passed from the controller — no per-card query.
                                                $isFavMarket = isset($favoriteMarketIds) && $favoriteMarketIds->contains($market->id);
                                            @endphp
                                            <button type="button" class="fav-btn favorite-market-btn"
                                                    data-url="{{ route('favorites.toggle.market') }}"
                                                    data-market-id="{{ $market->id }}"
                                                    title="{{ $isFavMarket ? 'Remove from favorites' : 'Save to favorites' }}"
                                                    aria-label="Toggle favorite market">
                                                <i class="bi {{ $isFavMarket ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                                            </button>
                                        @endif
                                    @endauth
                                    @php $openDays = $market->openDays(); @endphp
                                    <span class="market-status-tag {{ $market->status === 'active' ? '' : 'inactive' }}">{{ $market->openDaysLabel() }}</span>
                                    <img src="{{ $market->image_url }}" alt="{{ $market->name }}"
                                     loading="lazy"
                                         width="600" height="400" loading="lazy"
                                         onerror="this.onerror=null;this.src=this.dataset.fallback;"
                                         data-fallback="{{ asset('images/placeholders/market.png') }}">
                                </div>
                                <div class="market-details">
                                    <h3 class="market-title">{{ $market->name }}</h3>
                                    <div class="market-meta">
                                        <span><i class="bi bi-geo-alt-fill"></i>{{ $market->address }}</span>
                                    </div>
                                    <div class="mb-3">
                                        @if ($openDays->count() > 1)
                                            <p class="multi-market-note mb-2"><i class="bi bi-calendar-week me-1"></i>Trades {{ $openDays->count() }} days weekly</p>
                                        @endif
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
                            <div class="ml-note"><i class="bi bi-info-circle"></i>No markets match that day. Try another filter.</div>
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">{{ $markets->links() }}</div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}" defer></script>
<script>if (typeof L === 'undefined') document.write('<script src="https:\/\/unpkg.com\/leaflet@1.9.4\/dist\/leaflet.js"><\/script>');</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var map = L.map('map').setView([24.8607, 67.0011], 11); // Karachi
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
    });

    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('findNearbyBtn');
        var statusEl = document.getElementById('nearbyStatus');
        var resultsEl = document.getElementById('nearbyResults');
        var addressEl = document.getElementById('nearbyAddress');
        var countEl = document.getElementById('nearbyCount');
        var listEl = document.getElementById('nearbyMarkets');
        if (!btn) return;

        function showStatus(type, message) {
            statusEl.className = 'small mt-2 ' + (type === 'error' ? 'text-danger' : 'text-muted');
            statusEl.innerHTML = message;
        }

        function render(data) {
            resultsEl.classList.remove('d-none');
            addressEl.textContent = data.user_location.address
                || ('Coordinates: ' + data.user_location.latitude.toFixed(5) + ', ' + data.user_location.longitude.toFixed(5));

            var markets = data.markets || [];
            countEl.textContent = markets.length
                ? markets.length + ' market' + (markets.length === 1 ? '' : 's') + ' within ' + data.radius_km + ' km'
                : '';
            listEl.innerHTML = '';

            if (!markets.length) {
                listEl.innerHTML = '<div class="ml-note mb-0"><i class="bi bi-compass me-1"></i>' +
                    'No markets found within ' + data.radius_km + ' km. Try widening the map instead.</div>';
                return;
            }

            markets.forEach(function (m) {
                var a = document.createElement('a');
                a.href = m.url;
                a.className = 'card-ml p-3 d-flex justify-content-between align-items-center text-decoration-none nearby-market-item';
                a.innerHTML = '<div class="me-2"><div class="fw-semibold small">' + m.name + '</div>' +
                    '<div class="small text-muted">' + m.address + '</div>' +
                    '<div class="small text-muted"><i class="bi bi-people me-1"></i>' + m.farmers_count + ' farmer' + (m.farmers_count === 1 ? '' : 's') + '</div></div>' +
                    '<span class="badge bg-ml-green-light text-ml-green fw-semibold flex-shrink-0">' + m.distance_km + ' km</span>';
                listEl.appendChild(a);
            });
        }

        btn.addEventListener('click', function () {
            if (!navigator.geolocation) {
                showStatus('error', '<i class="bi bi-x-circle me-1"></i>Geolocation is not supported by this browser.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-arrow-repeat spin-icon me-1"></i> Locating...';
            showStatus('info', '<i class="bi bi-hourglass-split me-1"></i>Waiting for your location...');

            navigator.geolocation.getCurrentPosition(function (pos) {
                var lat = pos.coords.latitude;
                var lng = pos.coords.longitude;
                showStatus('info', '<i class="bi bi-hourglass-split me-1"></i>Finding markets near you...');

                fetch('/api/markets/nearby?latitude=' + encodeURIComponent(lat) + '&longitude=' + encodeURIComponent(lng) + '&radius=10')
                    .then(function (r) { if (!r.ok) throw new Error('Request failed'); return r.json(); })
                    .then(function (data) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-crosshair2 me-1"></i> Find Markets Near Me';
                        statusEl.className = 'small mt-2 d-none';
                        render(data);
                        if (typeof L !== 'undefined') {
                            try {
                                map.flyTo([lat, lng], 13, { duration: 0.8 }); // lat/lng from the position callback — fixed scope bug
                                L.circleMarker([lat, lng], {
                                    radius: 8, color: '#889721', weight: 2, fillColor: '#a2b231', fillOpacity: 0.85,
                                    className: 'market-you-marker',
                                }).addTo(map).bindPopup('You are here');
                            } catch (e) {}
                        }
                    })
                    .catch(function () {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-crosshair2 me-1"></i> Find Markets Near Me';
                        showStatus('error', '<i class="bi bi-x-circle me-1"></i>Could not load nearby markets. Please try again.');
                    });
            }, function (err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-crosshair2 me-1"></i> Find Markets Near Me';
                var messages = {
                    1: 'Location permission denied. Allow location access and try again.',
                    2: 'Location unavailable right now. Please try again.',
                    3: 'Location request timed out. Please try again.',
                };
                showStatus('error', '<i class="bi bi-x-circle me-1"></i>' + (messages[err.code] || 'Could not get your location.'));
            }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 });
        });
    });
</script>
@endpush
@endsection

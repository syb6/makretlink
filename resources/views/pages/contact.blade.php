@extends('layouts.app')

@section('title', 'Contact Us')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('content')
<section class="page-banner anim-up">
    <i class="bi bi-chat-heart page-banner-icon" aria-hidden="true"></i>
    <div class="container">
        <span class="badge-sub">GET IN TOUCH</span>
        <h1 class="section-title mb-1">Contact <span class="accent">Us</span></h1>
        <p class="section-subtitle mb-0">Questions about orders, selling, or partnerships? We're here to help.</p>
    </div>
</section>
<section class="py-4 py-md-5">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-5">
                <div class="card-ml p-4 mb-4 reveal">
                    <h5 class="fw-bold mb-3">Get in touch</h5>
                    <p class="mb-2"><i class="bi bi-geo-alt me-2" style="color: var(--primary-dark)"></i>MarketLink — Gulshan-e-Iqbal, Karachi, Pakistan</p>
                    <p class="mb-2"><i class="bi bi-envelope me-2" style="color: var(--primary-dark)"></i><a href="mailto:hello@marketlink.test" class="text-decoration-none">hello@marketlink.test</a></p>
                    <p class="mb-2"><i class="bi bi-telephone me-2" style="color: var(--primary-dark)"></i><a href="tel:+92211111MarketLink" class="text-decoration-none">+92 21 111 111&nbsp;xxx</a></p>
                    <p class="mb-0"><i class="bi bi-clock me-2" style="color: var(--primary-dark)"></i>Mon–Sat, 8:00 – 18:00</p>
                </div>

                @php
                    // Show the real weekly schedule of our flagship market —
                    // sourced from the database so it can never drift.
                    $flagship = \App\Models\Market::where('name', 'Green Valley Community Market')
                        ->with('schedules')->first();
                    $flagshipOpenDays = $flagship?->openDays();
                @endphp
                @if ($flagship && $flagshipOpenDays?->isNotEmpty())
                    <div class="card-ml p-4 mb-4 reveal">
                        <h5 class="fw-bold mb-1"><i class="bi bi-shop me-2" style="color: var(--primary-dark)"></i>Visit the flagship market</h5>
                        <p class="small text-muted mb-3">{{ $flagship->address }}</p>
                        <ul class="list-unstyled mb-0">
                            @foreach ($flagshipOpenDays as $s)
                                <li class="d-flex justify-content-between border-bottom py-2">
                                    <span class="fw-semibold small">{{ \App\Models\MarketSchedule::DAYS[$s->day_of_week] }}</span>
                                    <span class="small text-muted">{{ $s->opening_time?->format('g:i A') }} – {{ $s->closing_time?->format('g:i A') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.send') }}" class="card-ml p-4 reveal">
                    <x-form-errors />
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Message</label>
                        <textarea name="message" rows="4" class="form-control" required>{{ old('message') }}</textarea>
                    </div>
                    <button class="btn btn-ml w-100">Send message</button>
                </form>
            </div>
            <div class="col-lg-7">
                <div id="map" data-map-lat="24.9197" data-map-lng="67.0990" data-map-zoom="14"></div>
                <p class="small text-muted mt-2">
                    <i class="bi bi-info-circle me-1"></i>Our flagship <strong>Green Valley Community Market</strong> on University Road, Gulshan-e-Iqbal.
                </p>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('map');
        if (!el || typeof L === 'undefined') {
            // Fallback if the deferred Leaflet script is unavailable offline
            setTimeout(function () {
                if (el && typeof L !== 'undefined' && !el.__leafletReady) initMap(el);
            }, 600);
            return;
        }
        initMap(el);

        function initMap(el) {
            if (el.__leafletReady) return;
            el.__leafletReady = true;
            var lat = parseFloat(el.dataset.mapLat), lng = parseFloat(el.dataset.mapLng);
            var map = L.map(el).setView([lat, lng], parseInt(el.dataset.mapZoom || '14', 10));
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            L.marker([lat, lng]).addTo(map)
                .bindPopup('<strong>Green Valley Community Market</strong><br>University Road, Gulshan-e-Iqbal, Karachi')
                .openPopup();
        }
    });
</script>
@endpush
@endsection

@extends('layouts.app')

@section('title', 'Contact Us')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge-sub">GET IN TOUCH</span>
            <h1 class="section-title mb-1">Contact <span class="accent">Us</span></h1>
            <p class="section-subtitle mb-0">Questions about orders, selling, or partnerships? We're here to help.</p>
        </div>

        <div class="row g-5">
            <div class="col-lg-5">
                <div class="card-ml p-4 mb-4">
                    <h5 class="fw-bold mb-3">Get in touch</h5>
                    <p class="mb-2"><i class="bi bi-geo-alt me-2" style="color: var(--primary-dark)"></i>123 Green Valley Rd, Market Town</p>
                    <p class="mb-2"><i class="bi bi-envelope me-2" style="color: var(--primary-dark)"></i>hello@marketlink.test</p>
                    <p class="mb-2"><i class="bi bi-telephone me-2" style="color: var(--primary-dark)"></i>(555) 010-2233</p>
                    <p class="mb-0"><i class="bi bi-clock me-2" style="color: var(--primary-dark)"></i>Mon–Sat, 8:00 – 18:00</p>
                </div>

                <form method="POST" action="{{ route('contact.send') }}" class="card-ml p-4">
                    <x-form-errors />
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Message</label>
                        <textarea name="message" rows="4" class="form-control" required></textarea>
                    </div>
                    <button class="btn btn-ml w-100">Send message</button>
                </form>
            </div>
            <div class="col-lg-7">
                <div id="map"></div>
                <p class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i>Our flagship Green Valley Community Market location.</p>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>if (typeof L === 'undefined') document.write('<script src="https:\/\/unpkg.com\/leaflet@1.9.4\/dist\/leaflet.js"><\/script>');</script>
<script>
    (function () {
        var map = L.map('map').setView([40.7128, -74.006], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        L.marker([40.7128, -74.006]).addTo(map)
            .bindPopup('<strong>MarketLink HQ</strong><br>Green Valley Community Market')
            .openPopup();
    })();
</script>
@endpush
@endsection

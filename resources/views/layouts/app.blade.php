<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="cart-add-url" content="{{ route('cart.add') }}">
    <title>@yield('title', 'Farm Fresh, Just a Click Away') - {{ config('app.name', 'MarketLink') }}</title>

    <script>
        // Apply the saved theme before CSS paints to avoid a flash of the wrong theme.
        (function () {
            var theme = localStorage.getItem('ml-theme');
            if (theme !== 'light' && theme !== 'dark') {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>

    {{-- Fonts are self-hosted via @font-face in app.css (display: swap).
         No preload: on single-threaded dev servers preloads queue ahead of
         the hero image and delay LCP; the CSS is render-blocking anyway. --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}?v={{ filemtime(public_path('css/app.min.css')) }}">
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100 {{ request()->is('admin', 'admin/*') ? 'is-admin-area' : '' }}">
<a class="skip-link" href="#main">Skip to content</a>

@php
    // The admin console renders its own chrome (sidebar + header), so it
    // opts out of the public navbar/footer entirely.
    $isAdminArea = request()->is('admin') || request()->is('admin/*');
@endphp

@if (! $isAdminArea)
    @include('layouts.partials.navbar')
@endif

<main id="main" tabindex="-1" class="flex-grow-1">
    @unless ($isAdminArea)
        <div class="container mt-3">
            @include('layouts.partials.flash')
        </div>
    @endunless
    @yield('content')
</main>

@if (! $isAdminArea)
    @include('layouts.partials.footer')
@endif

{{-- Global AI assistant widget — every non-admin, non-auth page --}}
@if (! $isAdminArea && ! request()->is('login') && ! request()->is('register') && ! request()->is('password/*'))
    @include('chatbot.widget')
@endif

<button class="back-to-top" id="backToTop" type="button" aria-label="Back to top"><i class="bi bi-arrow-up"></i></button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.min.js') }}?v={{ filemtime(public_path('js/app.min.js')) }}"></script>
@auth
    <script>
        // Refresh the notification badge every 30s while the tab is visible.
        setInterval(function () {
            if (document.hidden) return;
            fetch("{{ route('notifications.count') }}", { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var badge = document.getElementById('notif-count');
                    if (!badge) return;
                    badge.textContent = data.unread;
                    badge.classList.toggle('d-none', !data.unread);
                })
                .catch(function () {});
        }, 30000);
    </script>
@endauth
@if (file_exists(public_path('js/marketlink.min.js')))
    <script src="{{ asset('js/marketlink.min.js') }}?v={{ filemtime(public_path('js/marketlink.min.js')) }}"></script>
@elseif (file_exists(public_path('js/marketlink.js')))
    <script src="{{ asset('js/marketlink.js') }}"></script>
@endif
@if (file_exists(public_path('js/chatbot.min.js')))
    <script src="{{ asset('js/chatbot.min.js') }}?v={{ filemtime(public_path('js/chatbot.min.js')) }}"></script>
@elseif (file_exists(public_path('js/chatbot.js')))
    <script src="{{ asset('js/chatbot.js') }}"></script>
@endif
@stack('scripts')
</body>
</html>

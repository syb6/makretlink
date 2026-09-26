<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MarketLink') - {{ config('app.name', 'MarketLink') }}</title>

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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">

@if (!request()->is('admin') && !request()->is('admin/*'))
    @include('layouts.partials.navbar')
@endif

<main class="flex-grow-1">
    <div class="container mt-3">
        @include('layouts.partials.flash')
    </div>
    @yield('content')
</main>

@if (!request()->is('admin') && !request()->is('admin/*'))
    @include('layouts.partials.footer')
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script>window.cartAddUrl = "{{ route('cart.add') }}";</script>
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
<script src="{{ asset('js/marketlink.js') }}"></script>
@if (file_exists(public_path('js/chatbot.js')))
    <script src="{{ asset('js/chatbot.js') }}"></script>
@endif
@stack('scripts')
</body>
</html>

@extends('layouts.app')

@section('title', $exception->getStatusCode() . ' — ' . __('Whoops, something went wrong'))

@php
    $code = $exception->getStatusCode();

    $art = [
        404 => ['icon' => 'bi-search-heart',        'title' => 'This path grew over',      'text' => 'The page you are looking for was moved, harvested, or never planted. Let us guide you back to fresh produce.'],
        403 => ['icon' => 'bi-shield-lock',          'title' => 'Members-only stall',       'text' => 'You do not have permission to enter this area. If you believe this is a mistake, try signing in with the right account.'],
        419 => ['icon' => 'bi-hourglass-split',      'title' => 'Your session expired',     'text' => 'For security reasons the page expired while you were away. Refresh and try again — your basket is safe.'],
        429 => ['icon' => 'bi-speedometer',          'title' => 'Easy there, farmer',       'text' => 'Too many requests in a short time. Take a short breath and try again in a moment.'],
        500 => ['icon' => 'bi-tools',                'title' => 'Something broke on our side', 'text' => 'An unexpected error occurred while loading this page. Our team has been notified — please try again shortly.'],
        503 => ['icon' => 'bi-cloud-slash',          'title' => 'Market closed for cleaning',  'text' => 'We are down for a short maintenance break. Fresh goods (and the site) will be back very soon.'],
    ];
    $view = $art[$code] ?? ['icon' => 'bi-bug', 'title' => 'Something went wrong', 'text' => 'An unexpected error occurred. Please try again or head back to the marketplace.'];
@endphp

@section('content')
<section class="error-hero">
    <div class="hero-bg-glow" aria-hidden="true"></div>
    <div class="hero-bg-glow-2" aria-hidden="true"></div>

    <div class="container error-box">
        <span class="error-code anim-up" aria-hidden="true">{{ $code }}</span>

        <div class="error-icon anim-pop" aria-hidden="true" style="animation-delay: 120ms">
            <i class="bi {{ $view['icon'] }}"></i>
        </div>

        <h1 class="error-title anim-up" style="animation-delay: 180ms">{{ $view['title'] }}</h1>
        <p class="error-text anim-up" style="animation-delay: 240ms">{{ $view['text'] }}</p>

        <div class="error-actions anim-up" style="animation-delay: 300ms">
            <a href="{{ route('home') }}" class="btn btn-ml"><i class="bi bi-house-door me-1"></i> Back to Home</a>
            <a href="{{ route('products.index') }}" class="btn btn-outline-ml"><i class="bi bi-basket me-1"></i> Browse Products</a>
            <button type="button" class="btn btn-outline-ml" onclick="history.back()"><i class="bi bi-arrow-left me-1"></i> Go Back</button>
        </div>

        <div class="error-help anim-up" style="animation-delay: 360ms">
            <i class="bi bi-chat-dots me-1"></i> Need a hand? Ask the <strong>MarketLink Assistant</strong> from the chat bubble — bottom right.
        </div>
    </div>
</section>
@endsection

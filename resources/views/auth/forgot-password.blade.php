@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
<section class="py-5">
    <div class="auth-container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <div class="auth-icon mb-3"><i class="bi bi-key"></i></div>
                <h1 class="section-title mb-1">Forgot your password?</h1>
                <p class="text-muted small mb-0">Enter your account email and we'll send you a secure reset link. Links expire after <strong>30 minutes</strong> and work only once.</p>
            </div>

            <x-form-errors />

            @if (session('reset-status'))
                <div class="ml-alert success" role="status" data-ml-alert data-ttl="8000">
                    <div class="ml-alert-icon"><i class="bi bi-check-circle-fill"></i></div>
                    <div class="ml-alert-body">
                        <div class="ml-alert-msg">
                            <i class="bi bi-envelope-check me-1"></i> {{ session('reset-status') }}
                            <div class="small mt-1 opacity-75">The link <strong>expires after 30 minutes</strong> and can be used only once.</div>
                        </div>
                    </div>
                    <button type="button" class="ml-alert-close" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
                    <span class="ml-alert-progress" aria-hidden="true" style="--ttl: 8s"></span>
                </div>
            @endif

            @if (session('reset-newest'))
                <div class="small text-muted mb-2">
                    <i class="bi bi-envelope-check me-1"></i> A fresh link is on its way to your inbox. Only the <strong>newest</strong> email's link works — older reset links are no longer valid.
                </div>
            @endif

            @if (session('reset-url'))
                {{-- Dev mode (log mailer): the "email" is shown here instead --}}
                <div class="dev-reset-link">
                    <div class="fw-semibold mb-1"><i class="bi bi-terminal me-1"></i>Local mail preview — your reset link (valid until {{ session('reset-expiry') }}):</div>
                    <a href="{{ session('reset-url') }}" class="dev-reset-url">{{ session('reset-url') }}</a>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="email">Email address</label>
                    <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required placeholder="name@example.com" autofocus>
                    
                </div>
                <button type="submit" class="btn btn-ml w-100 mb-3"><i class="bi bi-send me-1"></i> Send reset link</button>
                <div class="text-center small">
                    <a href="{{ route('login') }}" class="fw-semibold text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

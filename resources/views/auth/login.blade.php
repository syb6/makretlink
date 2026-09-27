@extends('layouts.app')

@section('title', 'Log In')

@section('content')
<div class="auth-main">
    <div class="auth-container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <a href="{{ route('home') }}" class="logo-brand justify-content-center mb-3"><i class="bi bi-basket2-fill"></i>Market<span>Link</span></a>
                <h1 class="h3 mb-2">Welcome Back</h1>
                <p class="text-muted small mb-0">Log in to manage your stall or view your pre-orders.</p>
            </div>

            <x-form-errors />
            <form method="POST" action="{{ route('login.post') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required placeholder="name@example.com">
                    </div>
                    @error('email')<div class="invalid-feedback-ml show">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label mb-0">Password</label>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required placeholder="Enter your password">
                    </div>
                    @error('password')<div class="invalid-feedback-ml show">{{ $message }}</div>@enderror
                    <div class="text-end mt-1">
                        <a href="{{ route('password.request') }}" class="small text-decoration-none fw-semibold">Forgot password?</a>
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small text-muted" for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn btn-ml w-100 py-2"><i class="bi bi-box-arrow-in-right me-1"></i> Log In</button>
            </form>

            <div class="text-center mt-4 small">
                <span class="text-muted">New to MarketLink?</span>
                <a href="{{ route('register') }}" class="fw-semibold text-decoration-none">Create an account</a>
            </div>

            <hr class="my-4">
            <div class="small text-muted text-center lh-lg">
                <strong>Demo accounts</strong> (password: <code>password</code>)<br>
                admin@marketlink.test · farmer1@marketlink.test · customer1@marketlink.test
            </div>
        </div>
    </div>
</div>
@endsection

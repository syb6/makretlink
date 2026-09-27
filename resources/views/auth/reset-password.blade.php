@extends('layouts.app')

@section('title', 'Choose a New Password')

@section('content')
<section class="py-5">
    <div class="auth-container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <div class="auth-icon mb-3"><i class="bi bi-shield-lock"></i></div>
                <h1 class="section-title mb-1">Choose a new password</h1>
                <p class="text-muted small mb-0">
                    <i class="bi bi-hourglass-split me-1"></i>Reset links expire <strong>30 minutes</strong> after they are sent and work only once.
                </p>
            </div>

            <x-form-errors />

            <form method="POST" action="{{ route('password.update') }}" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="email">Email address</label>
                    <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $email) }}" required>
                    @error('email')<div class="invalid-feedback-ml show">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="password">New password</label>
                    <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                           required autocomplete="new-password" placeholder="At least 8 characters">
                    @error('password')<div class="invalid-feedback-ml show">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="password_confirmation">Repeat new password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password" placeholder="Same password again">
                </div>

                <button type="submit" class="btn btn-ml w-100 mb-3"><i class="bi bi-check2-circle me-1"></i> Change password</button>
                <div class="text-center small">
                    <a href="{{ route('login') }}" class="fw-semibold text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

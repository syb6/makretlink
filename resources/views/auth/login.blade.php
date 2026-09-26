@extends('layouts.app')

@section('title', 'Login')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="card card-ml border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <span class="brand-leaf" style="width:48px;height:48px;font-size:1.4rem;"><i class="bi bi-basket2-fill"></i></span>
                            <h1 class="h4 fw-bold mt-3 mb-1">Welcome back</h1>
                            <p class="text-muted small mb-0">Log in to manage your orders, stock and more.</p>
                        </div>

                        <form method="POST" action="{{ route('login.post') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label small" for="remember">Remember me</label>
                            </div>
                            <button type="submit" class="btn btn-ml w-100 py-2 fw-semibold">Log In</button>
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
        </div>
    </div>
</section>
@endsection

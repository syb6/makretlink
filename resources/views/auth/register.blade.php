@extends('layouts.app')

@section('title', 'Register')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-7">
                <div class="card card-ml border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <span class="brand-leaf" style="width:48px;height:48px;font-size:1.4rem;"><i class="bi bi-basket2-fill"></i></span>
                            <h1 class="h4 fw-bold mt-3 mb-1">Join MarketLink</h1>
                            <p class="text-muted small mb-0">Shop fresh produce or start selling at your local market.</p>
                        </div>

                        <ul class="nav nav-pills justify-content-center gap-2 mb-4" id="roleTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $role === 'customer' ? 'active' : '' }} px-4" id="tab-customer" data-bs-toggle="pill" data-role="customer" type="button">🛒 Customer</button>
                            </li>
            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $role === 'farmer' ? 'active' : '' }} px-4" id="tab-farmer" data-role="farmer" type="button">🌾 Farmer</button>
                            </li>
                        </ul>

                        <form method="POST" action="{{ route('register.post') }}" id="registerForm">
                            @csrf
                            <input type="hidden" name="role" id="roleInput" value="{{ $role }}">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Full name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Phone number <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required>
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Confirm password <span class="text-danger">*</span></label>
                                    <input type="password" name="password_confirmation" class="form-control" required>
                                </div>

                                {{-- Customer fields --}}
                                <div class="col-12 role-fields role-customer {{ $role === 'customer' ? '' : 'd-none' }}">
                                    <label class="form-label fw-semibold">Delivery / contact address <span class="text-danger">*</span></label>
                                    <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror" placeholder="Street, city...">{{ old('address') }}</textarea>
                                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                {{-- Farmer fields --}}
                                <div class="role-fields role-farmer {{ $role === 'farmer' ? '' : 'd-none' }}">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Stall / business name <span class="text-danger">*</span></label>
                                            <input type="text" name="business_name" class="form-control @error('business_name') is-invalid @enderror" value="{{ old('business_name') }}">
                                            @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Contact person</label>
                                            <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Farm / business address</label>
                                            <textarea name="business_address" rows="2" class="form-control">{{ old('business_address') }}</textarea>
                                            <div class="form-text">Farmer accounts are reviewed by an administrator before you can list products.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-ml w-100 py-2 fw-semibold mt-4">Create account</button>
                        </form>

                        <div class="text-center mt-4 small">
                            <span class="text-muted">Already have an account?</span>
                            <a href="{{ route('login') }}" class="fw-semibold text-decoration-none">Log in</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    document.querySelectorAll('#roleTabs .nav-link').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#roleTabs .nav-link').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var role = btn.dataset.role;
            document.getElementById('roleInput').value = role;
            document.querySelector('.role-customer').classList.toggle('d-none', role !== 'customer');
            document.querySelector('.role-farmer').classList.toggle('d-none', role !== 'farmer');
        });
    });
</script>
@endpush
@endsection

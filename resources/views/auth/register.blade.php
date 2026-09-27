@extends('layouts.app')

@section('title', 'Sign Up')

@section('content')
<div class="auth-main">
    <div class="auth-container wide">
        <div class="auth-card">
            <div class="text-center mb-4">
                <a href="{{ route('home') }}" class="logo-brand justify-content-center mb-3"><i class="bi bi-basket2-fill"></i>Market<span>Link</span></a>
                <h1 class="h3 mb-2">Create Your Account</h1>
                <p class="text-muted small mb-0">Join as a customer or farmer to get started on MarketLink.</p>
            </div>

            <x-form-errors />
            <form method="POST" action="{{ route('register.post') }}" id="registerForm" novalidate>
                @csrf
                <input type="hidden" name="role" id="roleInput" value="{{ $role }}">

                <label class="form-label d-block mb-2">I want to join as</label>
                <div class="role-selector-wrapper">
                    <div>
                        <input type="radio" class="role-btn-input" name="roleRadio" id="roleCustomer" value="customer" {{ $role === 'customer' ? 'checked' : '' }}>
                        <label class="role-btn-label" for="roleCustomer">
                            <i class="bi bi-bag-heart"></i>
                            <span>Customer</span>
                            <small>Pre-order fresh local items</small>
                        </label>
                    </div>
                    <div>
                        <input type="radio" class="role-btn-input" name="roleRadio" id="roleFarmer" value="farmer" {{ $role === 'farmer' ? 'checked' : '' }}>
                        <label class="role-btn-label" for="roleFarmer">
                            <i class="bi bi-shop"></i>
                            <span>Farmer</span>
                            <small>List produce &amp; manage stall</small>
                        </label>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="fullName" class="form-label">Full Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="fullName" name="name" value="{{ old('name') }}" required placeholder="e.g. Jane Smith">
                        
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phoneNumber" class="form-label">Phone</label>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phoneNumber" name="phone" value="{{ old('phone') }}" required placeholder="+1 (555) 000-0000">
                        
                    </div>
                </div>

                <div class="mb-3">
                    <label for="emailAddress" class="form-label">Email Address</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="emailAddress" name="email" value="{{ old('email') }}" required placeholder="name@example.com">
                    
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="accountPassword" class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="accountPassword" name="password" required placeholder="Create a password">
                        
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="passwordConfirmation" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="passwordConfirmation" name="password_confirmation" required placeholder="Repeat your password">
                    </div>
                </div>

                {{-- Customer fields --}}
                <div class="role-fields role-customer {{ $role === 'customer' ? '' : 'd-none' }}">
                    <label class="form-label">Delivery / contact address <span class="text-danger">*</span></label>
                    <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror" placeholder="Street, city...">{{ old('address') }}</textarea>
                    
                </div>

                {{-- Farmer fields --}}
                <div class="role-fields role-farmer {{ $role === 'farmer' ? '' : 'd-none' }}">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stall / business name <span class="text-danger">*</span></label>
                            <input type="text" name="business_name" class="form-control @error('business_name') is-invalid @enderror" value="{{ old('business_name') }}">
                            
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact person <span class="optional-tag">(optional)</span></label>
                            <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Farm / business address</label>
                            <textarea name="business_address" rows="2" class="form-control">{{ old('business_address') }}</textarea>
                            <div class="form-text">Farmer accounts are reviewed by an administrator before you can list products.</div>
                        </div>
                    </div>
                </div>

                <div class="form-check mb-4 mt-2">
                    <input class="form-check-input" type="checkbox" id="termsCheck" required>
                    <label class="form-check-label small text-muted" for="termsCheck">
                        I agree to MarketLink's <a href="{{ route('about') }}" class="text-dark text-decoration-underline">Terms of Service</a> and <a href="{{ route('about') }}" class="text-dark text-decoration-underline">Privacy Policy</a>.
                    </label>
                </div>

                <button type="submit" class="btn btn-ml w-100 py-2"><i class="bi bi-person-plus me-1"></i> Create Account</button>
            </form>

            <div class="text-center mt-4 small">
                <span class="text-muted">Already have an account?</span>
                <a href="{{ route('login') }}" class="fw-semibold text-decoration-none">Log in</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var roleInput = document.getElementById('roleInput');
        var customerRadio = document.getElementById('roleCustomer');
        var farmerRadio = document.getElementById('roleFarmer');

        function applyRole(role) {
            roleInput.value = role;
            document.querySelector('.role-customer').classList.toggle('d-none', role !== 'customer');
            document.querySelector('.role-farmer').classList.toggle('d-none', role !== 'farmer');
        }

        customerRadio.addEventListener('change', function () { if (this.checked) applyRole('customer'); });
        farmerRadio.addEventListener('change', function () { if (this.checked) applyRole('farmer'); });
        applyRole('{{ $role }}');
    })();
</script>
@endpush
@endsection

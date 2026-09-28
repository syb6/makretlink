@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <span class="badge-sub">ACCOUNT</span>
        <h1 class="section-title mb-4">Profile Settings</h1>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
    @include('layouts.partials._avatar', ['user' => $user, 'size' => 96])
    <div class="small text-muted mt-2">This photo shows next to your name across MarketLink.</div>
</div>
<x-form-errors />
<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="card-ml p-4">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email (login)</label>
                            <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Profile picture</label>
                            @include('components.image-picker', ['name' => 'profile_image', 'label' => 'profile picture', 'existing' => $user->avatarUrl(), 'hint' => 'JPG, PNG or WebP · max 2 MB · square works best', 'placeholder' => 'bi-person'])
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" rows="2" class="form-control" required>{{ old('address', $customer->address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">New password <span class="optional-tag">(leave blank to keep)</span></label>
                            <input type="password" name="password" class="form-control" autocomplete="new-password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm new password</label>
                            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                    <button class="btn btn-ml mt-4 px-4">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

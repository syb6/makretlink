@extends('layouts.app')

@section('title', 'Admin Profile')
@section('admin_title', 'Admin Profile')

@section('content')
<div class="app-container">
    @include('layouts.partials.admin-sidebar')
    <div class="main-wrapper">
        @include('layouts.partials.admin-header')

        <main class="p-3 p-md-4">
            <div class="mb-4">
                <h2 class="h3 mb-1">My Profile</h2>
                <p class="text-muted mb-0">Manage your administrator account details.</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="dashboard-card text-center">
                        <div class="dashboard-card-header"><h5 class="mb-0">Profile Photo</h5></div>
                        <div class="dashboard-card-body">
                            <div class="profile-photo-preview mx-auto mb-3">
                                @include('layouts.partials._avatar', ['user' => $user, 'size' => 112])
                            </div>
                            <p class="small text-muted mb-0">JPG, PNG or WebP — max 2&nbsp;MB. Shown next to your name across the console.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="dashboard-card">
                        <div class="dashboard-card-header"><h5 class="mb-0">Account Details</h5></div>
                        <div class="dashboard-card-body">
                            <x-form-errors />
                            <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="row g-3">
                                @csrf
                                @method('PUT')

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="name">Name</label>
                                    <input id="name" type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="phone">Phone</label>
                                    <input id="phone" type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="email">Email (login)</label>
                                    <input id="email" type="email" class="form-control" value="{{ $user->email }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">New photo</label>
                                    @include('components.image-picker', ['name' => 'profile_photo', 'label' => 'admin photo', 'existing' => $user->avatarUrl(), 'hint' => 'JPG, PNG or WebP · max 2 MB · square works best', 'placeholder' => 'bi-person-gear'])
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="password">New password <span class="optional-tag">(leave blank to keep)</span></label>
                                    <input id="password" type="password" name="password" class="form-control" autocomplete="new-password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="password_confirmation">Confirm password</label>
                                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-ml"><i class="bi bi-check2 me-1"></i> Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection

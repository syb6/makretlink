@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<section class="py-5">
    <div class="container">
        <h1 class="section-title mb-4">Profile <span class="accent">Settings</span></h1>

        <div class="row justify-content-center">
            <div class="col-lg-8">
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
                            <input type="file" name="profile_image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" rows="2" class="form-control" required>{{ old('address', $user->customerProfile->address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">New password (leave blank to keep)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm new password</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>
                    </div>
                    <button class="btn btn-ml mt-4 px-4">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

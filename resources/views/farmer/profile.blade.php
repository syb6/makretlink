@extends('layouts.app')

@section('title', 'Farmer Profile')

@section('content')
<section class="py-5">
    <div class="container">
        <h1 class="section-title mb-4">Stall <span class="accent">Profile</span></h1>

        <form method="POST" action="{{ route('farmer.profile.update') }}" enctype="multipart/form-data" class="card-ml p-4">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Stall / business name</label>
                    <input type="text" name="business_name" class="form-control" value="{{ old('business_name', $farmer->business_name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Contact person</label>
                    <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $farmer->contact_person) }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea name="address" rows="2" class="form-control" required>{{ old('address', $farmer->address) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" rows="3" class="form-control" placeholder="Tell customers about your farm...">{{ old('description', $farmer->description) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Profile image</label>
                    <input type="file" name="profile_image" class="form-control" accept="image/*">
                </div>
            </div>
            <button class="btn btn-ml mt-4 px-4">Save profile</button>
        </form>
    </div>
</section>
@endsection

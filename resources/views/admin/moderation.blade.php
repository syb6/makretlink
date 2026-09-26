@extends('layouts.app')

@section('title', 'Content Moderation')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        <div class="col-lg-2 col-md-3">@include('layouts.partials.admin-sidebar')</div>

        <div class="col-lg-10 col-md-9">
            <h1 class="section-title mb-4">Content <span class="accent">Moderation</span></h1>

            <ul class="nav nav-pills mb-4" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-products">Products</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-prev">Product reviews</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-frev">Farmer reviews</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-products">
                    <div class="card-ml p-2">
                        <table class="table table-ml align-middle mb-0">
                            <thead><tr><th>Product</th><th>Farmer</th><th>Price</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                                @foreach ($products as $product)
                                    <tr>
                                        <td class="small fw-semibold">{{ $product->name }}</td>
                                        <td class="small">{{ $product->farmer->business_name }}</td>
                                        <td class="small">${{ number_format($product->price, 2) }}</td>
                                        <td><span class="status-pill status-{{ $product->status }}">{{ $product->status }}</span></td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.moderation.product', $product) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm {{ $product->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                    {{ $product->status === 'active' ? 'Take down' : 'Restore' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-prev">
                    <div class="card-ml p-2">
                        <table class="table table-ml align-middle mb-0">
                            <thead><tr><th>Customer</th><th>Product</th><th>Rating</th><th>Comment</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                                @forelse ($productReviews as $review)
                                    <tr>
                                        <td class="small">{{ $review->customer->user->name }}</td>
                                        <td class="small">{{ $review->product->name }}</td>
                                        <td class="small">{{ $review->rating }}/5</td>
                                        <td class="small">{{ \Illuminate\Support\Str::limit($review->comment, 60) }}</td>
                                        <td><span class="status-pill status-{{ $review->status }}">{{ $review->status }}</span></td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.moderation.productReview', $review) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm {{ $review->status === 'visible' ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                    {{ $review->status === 'visible' ? 'Hide' : 'Show' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">No reviews.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-frev">
                    <div class="card-ml p-2">
                        <table class="table table-ml align-middle mb-0">
                            <thead><tr><th>Customer</th><th>Farmer</th><th>Rating</th><th>Comment</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                                @forelse ($farmerReviews as $review)
                                    <tr>
                                        <td class="small">{{ $review->customer->user->name }}</td>
                                        <td class="small">{{ $review->farmer->business_name }}</td>
                                        <td class="small">{{ $review->rating }}/5</td>
                                        <td class="small">{{ \Illuminate\Support\Str::limit($review->comment, 60) }}</td>
                                        <td><span class="status-pill status-{{ $review->status }}">{{ $review->status }}</span></td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.moderation.farmerReview', $review) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm {{ $review->status === 'visible' ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                    {{ $review->status === 'visible' ? 'Hide' : 'Show' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">No reviews.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

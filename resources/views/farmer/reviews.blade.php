@extends('layouts.app')

@section('title', 'My Reviews')

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <span class="badge-sub">FARMER MANAGEMENT PORTAL</span>
        <h1 class="section-title mb-4">Customer Reviews</h1>

        <div class="row g-4">
            @forelse ($reviews as $review)
                <div class="col-lg-6">
                    <div class="card-ml p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                            <div>
                                <strong>{{ $review->customer->user->name }}</strong>
                                <div class="small text-muted">Order {{ $review->order?->order_number }} · {{ $review->created_at->format('M j, Y') }}</div>
                            </div>
                            <span class="stars">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }} small"></i>
                                @endfor
                            </span>
                        </div>
                        <p class="small mb-2">{{ $review->comment ?? '—' }}</p>

                        @if ($review->farmer_reply)
                            <div class="reply-box small">
                                <strong>Your reply:</strong> {{ $review->farmer_reply }}
                            </div>
                        @else
                            <form method="POST" action="{{ route('farmer.reviews.reply', $review) }}" class="d-flex gap-2 mt-2">
                                @csrf
                                <input type="text" name="farmer_reply" class="form-control form-control-sm" placeholder="Write a reply..." required maxlength="1000">
                                <button class="btn btn-sm btn-outline-ml">Reply</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="alert alert-light border">No reviews yet.</div></div>
            @endforelse
        </div>

        <div class="mt-4">{{ $reviews->links() }}</div>
    </div>
</section>
@endsection

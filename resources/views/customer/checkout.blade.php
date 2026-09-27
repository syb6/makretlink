@extends('layouts.app')

@section('title', 'Checkout & Pickup')

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <span class="badge-sub">CUSTOMER MARKET PORTAL</span>
        <h1 class="section-title mb-4">Checkout &amp; Pickup</h1>

        <x-form-errors />
<form method="POST" action="{{ route('checkout.place') }}">
            @csrf
            <div class="row g-4">
                <div class="col-lg-8">
                    @foreach ($groups as $farmerMarketId => $items)
                        @php $fm = $items->first()->farmerMarket; @endphp
                        <div class="dashboard-card mb-3">
                            <div class="dashboard-card-header">
                                <div>
                                    <h3 class="h5 mb-0">{{ $fm->farmer->business_name }}</h3>
                                    <small class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $fm->market->name }} @if($fm->stall_location) · {{ $fm->stall_location }} @endif</small>
                                </div>
                                <span class="fw-semibold">Rs {{ number_format($items->sum(fn ($i) => $i->quantity * $i->price), 2) }}</span>
                            </div>
                            <div class="dashboard-card-body">
                                <label class="form-label fw-semibold small">Choose a pickup slot *</label>
                                @if ($slots[$farmerMarketId]->isEmpty())
                                    <div class="alert alert-warning py-2 small mb-0">No pickup slots currently offered for this stall. Remove items or check back later.</div>
                                @else
                                    <div class="row g-2">
                                        @foreach ($slots[$farmerMarketId] as $slot)
                                            <div class="col-md-6">
                                                <label class="d-block border rounded-3 p-2 d-flex gap-2 align-items-center h-100 {{ $loop->first ? 'border-success' : '' }}">
                                                    <input type="radio" name="slots[{{ $farmerMarketId }}]" value="{{ $slot->id }}" {{ $loop->first ? 'checked' : '' }} class="form-check-input mt-0">
                                                    <span class="small">
                                                        <strong>{{ $slot->pickup_date->format('D, M j') }}</strong> · {{ $slot->start_time->format('g:i A') }}–{{ $slot->end_time->format('g:i A') }}
                                                        <br><span class="text-muted">Order before {{ $slot->cutoff_at->format('M j, g:i A') }}</span>
                                                    </span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="card-ml p-4">
                        <label class="form-label fw-semibold">Note for the farmer (optional)</label>
                        <textarea name="note" rows="2" class="form-control" placeholder="e.g. please pick the ripest avocados"></textarea>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-ml p-4">
                        <h5 class="fw-bold mb-3">Order summary</h5>
                        @foreach ($groups as $farmerMarketId => $items)
                            @foreach ($items as $item)
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>{{ $item->product->name }} × {{ $item->quantity }}</span>
                                    <span>Rs {{ number_format($item->quantity * $item->price, 2) }}</span>
                                </div>
                            @endforeach
                        @endforeach
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total to pay at pickup</span>
                            <span class="product-price">Rs {{ number_format($cart->total(), 2) }}</span>
                        </div>
                        <button class="btn btn-ml w-100 mt-3" {{ $slots->every(fn ($s) => $s->isEmpty()) ? 'disabled' : '' }}>Place pre-order{{ $groups->count() > 1 ? 's' : '' }}</button>
                        <p class="small text-muted text-center mt-2 mb-0"><i class="bi bi-shield-check me-1"></i>No online payment — pay the farmer in person.</p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

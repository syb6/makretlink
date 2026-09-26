@extends('layouts.app')

@section('title', $farmer->business_name)

@section('content')
<section class="py-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ route('farmers.index') }}">Farmers</a></li>
                <li class="breadcrumb-item active">{{ $farmer->business_name }}</li>
            </ol>
        </nav>

        <div class="card-ml p-4 mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="brand-leaf" style="width:64px;height:64px;font-size:1.8rem;border-radius:18px;">
                            {{ strtoupper(substr($farmer->business_name, 0, 1)) }}
                        </span>
                        <div>
                            <h1 class="section-title mb-0">{{ $farmer->business_name }}</h1>
                            <span class="status-pill status-approved">Approved farmer</span>
                        </div>
                    </div>
                    <p class="text-muted mb-1"><i class="bi bi-person me-1"></i>{{ $farmer->contact_person }} · {{ $farmer->user->email }}</p>
                    <p class="text-muted mb-1"><i class="bi bi-geo-alt me-1"></i>{{ $farmer->address }}</p>
                    @if ($farmer->description)
                        <p class="mb-0">{{ $farmer->description }}</p>
                    @endif
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold mb-2">Operating at</h6>
                    @foreach ($farmer->farmerMarkets as $fm)
                        <div class="mb-2">
                            <a href="{{ route('markets.show', $fm->market) }}" class="fw-semibold text-decoration-none">{{ $fm->market->name }}</a>
                            <div class="small text-muted">
                                @foreach ($fm->schedules as $s)
                                    {{ \App\Models\MarketSchedule::DAYS[$s->day_of_week] }} {{ $s->start_time->format('gA') }}–{{ $s->end_time->format('gA') }}@if(!$loop->last), @endif
                                @endforeach
                                @if ($fm->stall_location)
                                    · {{ $fm->stall_location }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <h2 class="section-title h4 mb-3">Current weekly stock</h2>
        <div class="row g-4">
            @forelse ($stocks as $stock)
                @include('products._card', ['stock' => $stock])
            @empty
                <div class="col-12"><div class="alert alert-light border">This farmer has not published weekly stock yet.</div></div>
            @endforelse
        </div>
    </div>
</section>

@include('chatbot.widget')
@endsection

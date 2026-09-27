@extends('layouts.app')

@section('title', 'My Stalls')

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <span class="badge-sub">FARMER MANAGEMENT PORTAL</span>
        <h1 class="section-title mb-1">My Stalls</h1>
        <p class="text-muted mb-4">Choose which markets you attend and on which days — customers see this on your profile.</p>

        @if (! $farmer->isApproved())
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>Your stall is not approved yet — an administrator must approve it before you can join markets.
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="dashboard-card">
                    <div class="dashboard-card-header"><h3 class="h6 mb-0"><i class="bi bi-plus-circle text-success me-2"></i>Join a Market</h3></div>
                    <div class="dashboard-card-body">
                        @if ($availableMarkets->isEmpty())
                            <p class="small text-muted mb-0">You are already listed at every active market.</p>
                        @else
                            <x-form-errors />
<form method="POST" action="{{ route('farmer.stalls.store') }}">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Market</label>
                                    <select name="market_id" class="form-select form-select-sm" required>
                                        <option value="">&mdash; choose &mdash;</option>
                                        @foreach ($availableMarkets as $market)
                                            <option value="{{ $market->id }}">{{ $market->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Stall name</label>
                                    <input type="text" name="stall_name" class="form-control form-control-sm" placeholder="{{ $farmer->business_name }}" maxlength="150">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Stall location (row, landmark...)</label>
                                    <input type="text" name="stall_location" class="form-control form-control-sm" placeholder="e.g. Row A, Stall 3" maxlength="255">
                                </div>
                                <label class="form-label small fw-semibold mt-2">Days I attend</label>
                                <div>
                                    @foreach (\App\Models\MarketSchedule::DAYS as $i => $dayName)
                                        @php
                                            $openDay = $openDays->firstWhere('day_of_week', $i);
                                        @endphp
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="days[]" value="{{ $i }}" id="day-{{ $i }}" {{ $openDay ? '' : 'disabled' }}>
                                            <label class="form-check-label small" for="day-{{ $i }}">{{ substr($dayName, 0, 3) }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="form-text mb-2">Disabled days are days the market itself is closed.</div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">From</label>
                                        <input type="time" name="start_time" class="form-control form-control-sm" value="08:00">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">To</label>
                                        <input type="time" name="end_time" class="form-control form-control-sm" value="14:00">
                                    </div>
                                </div>
                                <button class="btn btn-ml w-100 btn-sm">Join market</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                @forelse ($stalls as $stall)
                    <div class="dashboard-card mb-3">
                        <div class="dashboard-card-header">
                            <div>
                                <h3 class="h5 mb-0">{{ $stall->market->name }}</h3>
                                <small class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $stall->stall_name ?? $farmer->business_name }} @if($stall->stall_location) · {{ $stall->stall_location }} @endif</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="status-pill status-{{ $stall->status }}">{{ $stall->status }}</span>
                                <x-form-errors />
<form method="POST" action="{{ route('farmer.stalls.toggle', $stall) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-ml">{{ $stall->status === 'active' ? 'Pause stall' : 'Activate stall' }}</button>
                                </form>
                            </div>
                        </div>
                        <div class="dashboard-card-body">
                            <h6 class="small fw-bold text-uppercase text-muted mb-2">Weekly schedule</h6>
                            <div class="row g-2">
                                @foreach (\App\Models\MarketSchedule::DAYS as $i => $dayName)
                                    @php
                                        $schedule = $stall->schedules->firstWhere('day_of_week', $i);
                                        $marketOpen = $openDays->contains('day_of_week', $i);
                                    @endphp
                                    <div class="col-md-6">
                                        <x-form-errors />
<form method="POST" action="{{ route('farmer.stalls.schedule', $stall) }}" class="d-flex gap-1 align-items-center">
                                            @csrf
                                            <input type="hidden" name="day_of_week" value="{{ $i }}">
                                            <span class="small fw-semibold" style="width:52px">{{ substr($dayName, 0, 3) }}</span>
                                            <input type="time" name="start_time" class="form-control form-control-sm" value="{{ $schedule?->start_time?->format('H:i') }}" {{ $marketOpen ? '' : 'disabled' }} aria-label="{{ $dayName }} start">
                                            <input type="time" name="end_time" class="form-control form-control-sm" value="{{ $schedule?->end_time?->format('H:i') }}" {{ $marketOpen ? '' : 'disabled' }} aria-label="{{ $dayName }} end">
                                            <button class="btn btn-sm btn-outline-ml" {{ $marketOpen ? '' : 'disabled' }} title="{{ $schedule ? 'Save day' : 'Market closed this day' }}">{{ $schedule ? 'Save' : '—' }}</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text">Clear both times and press Save to remove that day.</div>
                        </div>
                    </div>
                @empty
                    <div class="dashboard-card">
                        <div class="empty">
                            <i class="bi bi-shop"></i>
                            <strong>You have not joined any markets yet.</strong>
                            <p class="small mb-0">Use the form on the left to pick your first market.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection

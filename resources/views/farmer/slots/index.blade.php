@extends('layouts.app')

@section('title', 'Pickup Slots')

@section('content')
<section class="py-5">
    <div class="container">
        <h1 class="section-title mb-4">Pickup <span class="accent">Slots</span></h1>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-ml p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2"></i>New slot</h5>
                    <form method="POST" action="{{ route('farmer.slots.store') }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Stall / market</label>
                            <select name="farmer_market_id" class="form-select form-select-sm" required>
                                <option value="">— choose —</option>
                                @foreach ($farmerMarkets as $fm)
                                    <option value="{{ $fm->id }}">{{ $fm->market->name }} ({{ $fm->stall_name ?? 'stall' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Pickup date</label>
                            <input type="date" name="pickup_date" class="form-control form-control-sm" required min="{{ now()->toDateString() }}">
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Start</label>
                                <input type="time" name="start_time" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">End</label>
                                <input type="time" name="end_time" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Order cutoff</label>
                            <input type="datetime-local" name="cutoff_at" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Max orders (optional)</label>
                            <input type="number" min="1" name="max_orders" class="form-control form-control-sm">
                        </div>
                        <button class="btn btn-ml w-100 btn-sm">Create slot</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                @forelse ($slots as $date => $daySlots)
                    <div class="card-ml p-3 mb-3">
                        <h6 class="fw-bold mb-2"><i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-ml align-middle mb-0">
                                <thead><tr><th>Stall</th><th>Window</th><th>Cutoff</th><th>Max</th><th>Orders</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($daySlots as $slot)
                                        <tr>
                                            <td class="small">{{ $slot->farmerMarket->market->name }}</td>
                                            <td class="small">{{ $slot->start_time }}–{{ $slot->end_time }}</td>
                                            <td class="small text-danger">{{ $slot->cutoff_at->format('M j, g:i A') }}</td>
                                            <td class="small">{{ $slot->max_orders ?? '∞' }}</td>
                                            <td class="small">{{ $slot->orders()->count() }}</td>
                                            <td><span class="status-pill status-{{ $slot->status }}">{{ $slot->status }}</span></td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('farmer.slots.update', $slot) }}" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    @if ($slot->status === 'active')
                                                        <input type="hidden" name="status" value="inactive">
                                                        <button class="btn btn-sm btn-outline-secondary" title="Deactivate"><i class="bi bi-pause"></i></button>
                                                    @else
                                                        <input type="hidden" name="status" value="active">
                                                        <button class="btn btn-sm btn-outline-success" title="Activate"><i class="bi bi-play"></i></button>
                                                    @endif
                                                </form>
                                                <form method="POST" action="{{ route('farmer.slots.destroy', $slot) }}" class="d-inline" data-confirm="Delete this slot?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-light border">No pickup slots yet. Create your first slot on the left.</div>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection

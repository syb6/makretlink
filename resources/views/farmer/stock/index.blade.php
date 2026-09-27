@extends('layouts.app')

@section('title', 'Weekly Stock')

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <span class="badge-sub">FARMER MANAGEMENT PORTAL</span>
                <h1 class="section-title mb-0">Weekly Stock</h1>
            </div>
            <div class="btn-group">
                <a href="?week={{ $prevWeek }}" class="btn btn-outline-ml btn-sm">&larr; {{ \Carbon\Carbon::parse($prevWeek)->format('M j') }}</a>
                <span class="btn btn-outline-secondary btn-sm disabled">Week of {{ $weekStart->format('M j, Y') }}</span>
                <a href="?week={{ $nextWeek }}" class="btn btn-outline-ml btn-sm">{{ \Carbon\Carbon::parse($nextWeek)->format('M j') }} &rarr;</a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="dashboard-card mb-4">
                    <div class="dashboard-card-header"><h3 class="h6 mb-0"><i class="bi bi-plus-circle text-success me-2"></i>Add Stock</h3></div>
                    <div class="dashboard-card-body">
                        <form method="POST" action="{{ route('farmer.stock.store') }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Product</label>
                                <select name="product_id" class="form-select form-select-sm" required>
                                    <option value="">&mdash; choose &mdash;</option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Stall / market</label>
                                <select name="farmer_market_id" class="form-select form-select-sm" required>
                                    <option value="">&mdash; choose &mdash;</option>
                                    @foreach ($farmerMarkets as $fm)
                                        <option value="{{ $fm->id }}">{{ $fm->market->name }} ({{ $fm->stall_name ?? 'stall' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Quantity available</label>
                                <input type="number" step="0.5" min="0" name="quantity" class="form-control form-control-sm" required>
                            </div>
                            <button class="btn btn-ml w-100 btn-sm">Save weekly stock</button>
                        </form>
                    </div>
                </div>

                <div class="dashboard-card mb-4">
                    <div class="dashboard-card-header"><h3 class="h6 mb-0"><i class="bi bi-file-earmark-arrow-down text-success me-2"></i>Apply Templates</h3></div>
                    <div class="dashboard-card-body">
                        <p class="small text-muted">Copies saved default quantities into the selected week for products that have no stock row yet.</p>
                        <form method="POST" action="{{ route('farmer.stock.templates.apply') }}">
                            @csrf
                            <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Stall / market</label>
                                <select name="farmer_market_id" class="form-select form-select-sm" required>
                                    <option value="">&mdash; choose &mdash;</option>
                                    @foreach ($farmerMarkets as $fm)
                                        <option value="{{ $fm->id }}">{{ $fm->market->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-outline-ml w-100 btn-sm">Apply templates to this week</button>
                        </form>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="dashboard-card-header"><h3 class="h6 mb-0"><i class="bi bi-file-earmark-plus text-success me-2"></i>Save a Template</h3></div>
                    <div class="dashboard-card-body">
                        <p class="small text-muted">Stores a default quantity per product &amp; stall so future weeks can be filled in one click.</p>
                        <form method="POST" action="{{ route('farmer.stock.templates.save') }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Product</label>
                                <select name="product_id" class="form-select form-select-sm" required>
                                    <option value="">&mdash; choose &mdash;</option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Stall / market</label>
                                <select name="farmer_market_id" class="form-select form-select-sm" required>
                                    <option value="">&mdash; choose &mdash;</option>
                                    @foreach ($farmerMarkets as $fm)
                                        <option value="{{ $fm->id }}">{{ $fm->market->name }} ({{ $fm->stall_name ?? 'stall' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Default quantity</label>
                                <input type="number" step="0.5" min="0" name="default_quantity" class="form-control form-control-sm" required>
                            </div>
                            <button class="btn btn-outline-ml w-100 btn-sm">Save template</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="dashboard-card flush">
                    <div class="table-responsive">
                        <table class="table table-ml align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Stall</th>
                                    <th>Qty</th>
                                    <th>Available</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($stocks as $stock)
                                    <tr>
                                        <td class="small fw-semibold">{{ $stock->product->name }}</td>
                                        <td class="small">{{ $stock->farmerMarket->market->name }}</td>
                                        <td class="small">{{ $stock->quantity }}</td>
                                        <td class="small">{{ $stock->available_quantity }}</td>
                                        <td><span class="status-pill status-{{ $stock->status }}">{{ str_replace('_', ' ', $stock->status) }}</span></td>
                                        <td class="text-end text-nowrap">
                                            <button class="btn btn-sm btn-outline-ml" data-bs-toggle="modal" data-bs-target="#editStock-{{ $stock->id }}"><i class="bi bi-pencil"></i> Edit</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No stock rows for this week yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Edit modals (kept outside the table so the markup stays valid) --}}
@foreach ($stocks as $stock)
    <div class="modal fade" id="editStock-{{ $stock->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('farmer.stock.update', $stock) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ $stock->product->name }} — {{ $stock->farmerMarket->market->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Total quantity for the week</label>
                        <input type="number" step="0.5" min="0" name="quantity" value="{{ $stock->quantity }}" class="form-control" required>
                        <div class="form-text">{{ $stock->quantity - $stock->available_quantity }} already sold/reserved — they stay sold when you save.</div>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="available" {{ $stock->status === 'available' ? 'selected' : '' }}>Available</option>
                            <option value="sold_out" {{ $stock->status === 'sold_out' ? 'selected' : '' }}>Sold out</option>
                            <option value="unavailable" {{ $stock->status === 'unavailable' ? 'selected' : '' }}>Temporarily unavailable</option>
                        </select>
                        <div class="form-text">Sold out / unavailable hides the item from customers.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-ml">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection

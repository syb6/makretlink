@extends('layouts.app')

@section('title', 'Products')

@section('content')
<section class="page-banner anim-up">
    <i class="bi bi-basket2-fill page-banner-icon" aria-hidden="true"></i>
    <div class="container">
        <span class="badge-sub">FRESH ARRIVALS</span>
        <h1 class="section-title mb-1">Browse Products</h1>
        <p class="section-subtitle mb-0">Live weekly stock — filter by category, market, day and price.</p>
    </div>
</section>
<section class="py-4 py-md-5">
    <div class="container">

        {{-- Filters --}}
        <form method="GET" id="productFilters" class="card card-ml p-3 mb-4 product-filter-card">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-4 col-6">
                    <label class="form-label small fw-semibold mb-1" for="filterQ">Search</label>
                    <input type="text" name="q" id="filterQ" class="form-control form-control-sm" placeholder="Tomatoes, honey..." value="{{ $filters['search'] }}">
                </div>
                <div class="col-lg-2 col-md-2 col-6">
                    <label class="form-label small fw-semibold mb-1" for="filterCategory">Category</label>
                    <select name="category" id="filterCategory" class="form-select form-select-sm" data-autosubmit>
                        <option value="">All</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" {{ $filters['category'] == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-2 col-6">
                    <label class="form-label small fw-semibold mb-1" for="filterMarket">Market</label>
                    <select name="market" id="filterMarket" class="form-select form-select-sm" data-autosubmit>
                        <option value="">All</option>
                        @foreach ($markets as $m)
                            <option value="{{ $m->id }}" {{ $filters['market'] == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-2 col-6">
                    <label class="form-label small fw-semibold mb-1" for="filterDay">Day</label>
                    <select name="day" id="filterDay" class="form-select form-select-sm" data-autosubmit>
                        <option value="">Any</option>
                        @foreach ($days as $i => $label)
                            <option value="{{ $i }}" {{ $filters['day'] === (string) $i ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-md-2 col-3">
                    <label class="form-label small fw-semibold mb-1" for="filterMin">Min Rs</label>
                    <input type="number" step="0.01" min="0" name="min_price" id="filterMin" class="form-control form-control-sm" value="{{ $filters['min'] }}">
                </div>
                <div class="col-lg-1 col-md-2 col-3">
                    <label class="form-label small fw-semibold mb-1" for="filterMax">Max Rs</label>
                    <input type="number" step="0.01" min="0" name="max_price" id="filterMax" class="form-control form-control-sm" value="{{ $filters['max'] }}">
                </div>
                <div class="col-lg-1 col-md-2 col-6 d-grid">
                    <button class="btn btn-ml btn-sm">Go</button>
                </div>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <span class="text-muted small">{{ $stocks->total() }} result(s)</span>
            {{-- Hidden inputs mirror the exact query-string names the controller
                 validates (q, category, market, day, min_price, max_price) so
                 changing the sort preserves every active filter. Values are
                 entity-encoded; Laravel's query builder treats blank strings as
                 absent via the ?-> guards. --}}
            <form method="GET" class="d-flex gap-2 align-items-center" id="sortForm">
                <input type="hidden" name="q" value="{{ $filters['search'] }}">
                <input type="hidden" name="category" value="{{ $filters['category'] }}">
                <input type="hidden" name="market" value="{{ $filters['market'] }}">
                <input type="hidden" name="day" value="{{ $filters['day'] }}">
                <input type="hidden" name="min_price" value="{{ $filters['min'] }}">
                <input type="hidden" name="max_price" value="{{ $filters['max'] }}">
                <label class="small text-muted" for="sortSelect">Sort</label>
                <select name="sort" id="sortSelect" class="form-select form-select-sm w-auto">
                    <option value="name" {{ $filters['sort'] === 'name' ? 'selected' : '' }}>Name</option>
                    <option value="price_low" {{ $filters['sort'] === 'price_low' ? 'selected' : '' }}>Price: low to high</option>
                    <option value="price_high" {{ $filters['sort'] === 'price_high' ? 'selected' : '' }}>Price: high to low</option>
                    <option value="newest" {{ $filters['sort'] === 'newest' ? 'selected' : '' }}>Newest</option>
                </select>
            </form>
        </div>

        <div class="row g-4">
            @forelse ($stocks as $stock)
                @include('products._card', ['stock' => $stock])
            @empty
                <div class="col-12"><div class="ml-note"><i class="bi bi-info-circle"></i>No products match your filters.</div></div>
            @endforelse
        </div>

        <div class="mt-4">{{ $stocks->links() }}</div>
    </div>
</section>

@endsection

@push('scripts')
    @include('products._scripts')
@endpush

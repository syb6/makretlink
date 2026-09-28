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

        <form method="GET" class="card card-ml p-3 mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-md-3 col-6">
                    <label class="form-label small fw-semibold mb-1">Search</label>
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Tomatoes, honey..." value="{{ $filters['search'] }}">
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-semibold mb-1">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" {{ $filters['category'] == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-semibold mb-1">Market</label>
                    <select name="market" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($markets as $m)
                            <option value="{{ $m->id }}" {{ $filters['market'] == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-semibold mb-1">Day</label>
                    <select name="day" class="form-select form-select-sm">
                        <option value="">Any</option>
                        @foreach ($days as $i => $label)
                            <option value="{{ $i }}" {{ $filters['day'] === (string) $i ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 col-3">
                    <label class="form-label small fw-semibold mb-1">Min Rs</label>
                    <input type="number" step="0.01" name="min_price" class="form-control form-control-sm" value="{{ $filters['min'] }}">
                </div>
                <div class="col-md-1 col-3">
                    <label class="form-label small fw-semibold mb-1">Max Rs</label>
                    <input type="number" step="0.01" name="max_price" class="form-control form-control-sm" value="{{ $filters['max'] }}">
                </div>
                <div class="col-md-1 col-6 d-grid">
                    <button class="btn btn-ml btn-sm">Go</button>
                </div>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <span class="text-muted small">{{ $stocks->total() }} result(s)</span>
            <form method="GET" class="d-flex gap-2 align-items-center">
                @foreach (collect($filters)->except('sort') as $key => $val)
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endforeach
                <label class="small text-muted">Sort</label>
                <select name="sort" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
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
                <div class="col-12"><div class="alert alert-light border">No products match your filters.</div></div>
            @endforelse
        </div>

        <div class="mt-4">{{ $stocks->links() }}</div>
    </div>
</section>

@endsection

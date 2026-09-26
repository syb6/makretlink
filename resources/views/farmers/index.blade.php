@extends('layouts.app')

@section('title', 'Farmers')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-3">
            <div>
                <h1 class="section-title mb-1">Our <span class="accent">Farmers</span></h1>
                <p class="text-muted mb-0">Meet the people growing your food.</p>
            </div>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <input type="text" name="q" class="form-control" style="max-width:220px" placeholder="Search farmers..." value="{{ $search }}">
                <select name="market" class="form-select" style="max-width:200px">
                    <option value="">All markets</option>
                    @foreach ($markets as $m)
                        <option value="{{ $m->id }}" {{ $selectedMarket == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-ml">Filter</button>
            </form>
        </div>

        <div class="row g-4">
            @forelse ($farmers as $farmer)
                <div class="col-sm-6 col-lg-4">
                    <div class="card-ml h-100 p-4 d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="brand-leaf" style="width:52px;height:52px;font-size:1.5rem;border-radius:14px;">
                                {{ strtoupper(substr($farmer->business_name, 0, 1)) }}
                            </span>
                            <div>
                                <h5 class="fw-bold mb-0">{{ $farmer->business_name }}</h5>
                                <small class="text-muted"><i class="bi bi-person me-1"></i>{{ $farmer->contact_person }}</small>
                            </div>
                        </div>
                        <p class="small text-muted mb-3">
                            {{ \Illuminate\Support\Str::limit($farmer->description ?: $farmer->address, 110) }}
                        </p>
                        <div class="mb-3">
                            @foreach ($farmer->farmerMarkets as $fm)
                                <span class="badge badge-soft mb-1"><i class="bi bi-geo-alt me-1"></i>{{ $fm->market->name }}</span>
                            @endforeach
                        </div>
                        <div class="mt-auto">
                            <a href="{{ route('farmers.show', $farmer) }}" class="btn btn-outline-ml btn-sm w-100">View profile & stock</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="alert alert-light border">No farmers found. Try different filters.</div></div>
            @endforelse
        </div>

        <div class="mt-4">{{ $farmers->links() }}</div>
    </div>
</section>
@endsection

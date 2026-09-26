@extends('layouts.app')

@section('title', 'Manage Markets')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        <div class="col-lg-2 col-md-3">@include('layouts.partials.admin-sidebar')</div>

        <div class="col-lg-10 col-md-9">
            <h1 class="section-title mb-4">Manage <span class="accent">Markets</span></h1>

            <div class="card-ml p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2"></i>Add market</h5>
                <form method="POST" action="{{ route('admin.markets.store') }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Name</label>
                            <input type="text" name="name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Address</label>
                            <input type="text" name="address" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Latitude</label>
                            <input type="number" step="0.000001" name="latitude" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Longitude</label>
                            <input type="number" step="0.000001" name="longitude" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Description</label>
                            <textarea name="description" rows="2" class="form-control form-control-sm"></textarea>
                        </div>
                        <input type="hidden" name="status" value="active">
                    </div>

                    <label class="form-label small fw-semibold mt-3">Weekly schedule (leave blank = closed)</label>
                    <div class="row g-2">
                        @foreach (['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i => $day)
                            <div class="col-md-3 col-6">
                                <div class="border rounded p-2">
                                    <div class="small fw-semibold mb-1">{{ $day }}</div>
                                    <div class="d-flex gap-1">
                                        <input type="time" name="days[{{ $i }}][open]" class="form-control form-control-sm" placeholder="Open">
                                        <input type="time" name="days[{{ $i }}][close]" class="form-control form-control-sm" placeholder="Close">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button class="btn btn-ml mt-3">Create market</button>
                </form>
            </div>

            @foreach ($markets as $market)
                <div class="card-ml p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1">{{ $market->name }} <span class="status-pill status-{{ $market->status }}">{{ $market->status }}</span></h5>
                            <div class="small text-muted mb-1"><i class="bi bi-geo-alt me-1"></i>{{ $market->address }}</div>
                            <div class="small">
                                @foreach ($market->schedules->sortBy('day_of_week') as $s)
                                    <span class="badge badge-soft {{ $s->is_closed ? 'opacity-50' : '' }}">
                                        {{ \App\Models\MarketSchedule::DAYS[$s->day_of_week] }}: {{ $s->is_closed ? 'closed' : $s->opening_time.'–'.$s->closing_time }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-ml" data-bs-toggle="modal" data-bs-target="#editMarket-{{ $market->id }}"><i class="bi bi-pencil"></i> Edit</button>
                            <form method="POST" action="{{ route('admin.markets.destroy', $market) }}" data-confirm="Delete this market?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Edit modal --}}
                <div class="modal fade" id="editMarket-{{ $market->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <form method="POST" action="{{ route('admin.markets.update', $market) }}" class="modal-content">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Edit {{ $market->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Name</label>
                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $market->name }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Address</label>
                                        <input type="text" name="address" class="form-control form-control-sm" value="{{ $market->address }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">Latitude</label>
                                        <input type="number" step="0.000001" name="latitude" class="form-control form-control-sm" value="{{ $market->latitude }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">Longitude</label>
                                        <input type="number" step="0.000001" name="longitude" class="form-control form-control-sm" value="{{ $market->longitude }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">Status</label>
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="active" {{ $market->status === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ $market->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Description</label>
                                        <textarea name="description" rows="2" class="form-control form-control-sm">{{ $market->description }}</textarea>
                                    </div>
                                </div>

                                <label class="form-label small fw-semibold mt-3">Weekly schedule</label>
                                <div class="row g-2">
                                    @foreach (['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i => $day)
                                        @php
                                            $sched = $market->schedules->firstWhere('day_of_week', $i);
                                        @endphp
                                        <div class="col-md-3 col-6">
                                            <div class="border rounded p-2">
                                                <div class="small fw-semibold mb-1">{{ $day }}</div>
                                                <div class="d-flex gap-1">
                                                    <input type="time" name="days[{{ $i }}][open]" value="{{ $sched?->opening_time && ! $sched->is_closed ? $sched->opening_time->format('H:i') : '' }}" class="form-control form-control-sm">
                                                    <input type="time" name="days[{{ $i }}][close]" value="{{ $sched?->closing_time && ! $sched->is_closed ? $sched->closing_time->format('H:i') : '' }}" class="form-control form-control-sm">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-ml">Save changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach

            <div class="mt-3">{{ $markets->links() }}</div>
        </div>
    </div>
</div>
@endsection

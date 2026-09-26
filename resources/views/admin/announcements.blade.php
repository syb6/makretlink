@extends('layouts.app')

@section('title', 'Announcements')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        <div class="col-lg-2 col-md-3">@include('layouts.partials.admin-sidebar')</div>

        <div class="col-lg-10 col-md-9">
            <h1 class="section-title mb-4">Platform <span class="accent">Announcements</span></h1>

            <div class="card-ml p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2"></i>New announcement</h5>
                <form method="POST" action="{{ route('admin.announcements.store') }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Title</label>
                            <input type="text" name="title" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="published">Published (visible on homepage)</option>
                                <option value="draft">Draft</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Message</label>
                            <textarea name="message" rows="3" class="form-control form-control-sm" required></textarea>
                        </div>
                    </div>
                    <button class="btn btn-ml mt-3">Publish</button>
                </form>
            </div>

            @forelse ($announcements as $a)
                <div class="card-ml p-3 mb-2 d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <strong>{{ $a->title }}</strong> <span class="status-pill status-{{ $a->status }}">{{ $a->status }}</span>
                        <div class="small text-muted mt-1">{{ $a->message }}</div>
                        <div class="small text-muted mt-1">By {{ $a->author?->name ?? 'system' }} · {{ $a->published_at?->format('M j, Y') ?? $a->created_at->format('M j, Y') }}</div>
                    </div>
                    <form method="POST" action="{{ route('admin.announcements.destroy', $a) }}" data-confirm="Delete this announcement?">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            @empty
                <div class="alert alert-light border">No announcements yet.</div>
            @endforelse

            <div class="mt-3">{{ $announcements->links() }}</div>
        </div>
    </div>
</div>
@endsection

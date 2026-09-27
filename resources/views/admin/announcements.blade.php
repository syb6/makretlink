@extends('layouts.app')

@section('title', 'Announcements')
@section('admin_title', 'Announcements')

@section('content')
<div class="app-container">
    @include('layouts.partials.admin-sidebar')
    <div class="main-wrapper">
        @include('layouts.partials.admin-header')

        <main class="p-3 p-md-4">
            <div class="mb-4">
                <h2 class="h3 mb-1">Platform Announcements</h2>
                <p class="text-muted mb-0">Publish messages shown on the public homepage.</p>
            </div>

            <div class="dashboard-card mb-4">
                <div class="dashboard-card-header"><h3 class="h5 mb-0"><i class="bi bi-plus-circle text-success me-2"></i>New Announcement</h3></div>
                <div class="dashboard-card-body">
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
            </div>

            @forelse ($announcements as $a)
                <div class="dashboard-card">
                    <div class="dashboard-card-body d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div class="flex-grow-1">
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
                </div>
            @empty
                <div class="alert alert-light border">No announcements yet.</div>
            @endforelse

            <div class="mt-3">{{ $announcements->links() }}</div>
        </main>

        <footer class="admin-footer">
            <div class="container text-center">
                <p class="text-muted small mb-0">&copy; {{ now()->year }} MarketLink Administration Portal.</p>
            </div>
        </footer>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Feedback Inbox')
@section('admin_title', 'Feedback Inbox')

@section('content')
<div class="app-container">
    @include('layouts.partials.admin-sidebar')
    <div class="main-wrapper">
        @include('layouts.partials.admin-header')

        <main class="p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="h3 mb-1">Feedback Inbox</h2>
                    <p class="text-muted mb-0">Messages sent through the public “Contact Us” form.</p>
                </div>
                @if ($unreadCount)
                    <span class="status-pill status-pending">{{ $unreadCount }} unread</span>
                @else
                    <span class="status-pill status-active">All caught up</span>
                @endif
            </div>

            <div class="dashboard-card flush">
                <div class="table-responsive">
                    <table class="table table-ml align-middle mb-0">
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>Message</th>
                                <th>Received</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($messages as $message)
                                <tr class="{{ $message->isRead() ? '' : 'fw-semibold' }}">
                                    <td>
                                        {{ $message->name }}
                                        <div class="small text-muted fw-normal">{{ $message->email }}</div>
                                    </td>
                                    <td class="small" style="max-width: 420px;">{{ $message->message }}</td>
                                    <td class="small text-muted text-nowrap">{{ $message->created_at->format('M j, Y g:i A') }}</td>
                                    <td>
                                        @if ($message->isRead())
                                            <span class="status-pill status-inactive">Read</span>
                                        @else
                                            <span class="status-pill status-pending">New</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <form method="POST" action="{{ route('admin.feedback.toggleRead', $message) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-ml" title="{{ $message->isRead() ? 'Mark as unread' : 'Mark as read' }}">
                                                <i class="bi {{ $message->isRead() ? 'bi-envelope' : 'bi-envelope-open' }}"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.feedback.destroy', $message) }}" class="d-inline" data-confirm="Delete this message?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="Delete message"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No feedback messages yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $messages->links() }}</div>
            </div>
        </main>

        <footer class="admin-footer">
            <div class="container text-center">
                <p class="text-muted small mb-0">&copy; {{ now()->year }} MarketLink Administration Portal.</p>
            </div>
        </footer>
    </div>
</div>
@endsection

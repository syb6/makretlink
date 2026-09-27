@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<section class="py-4 py-md-5">
    <div class="container" style="max-width: 860px">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <span class="badge-sub">INBOX</span>
                <h1 class="section-title mb-1">Notifications</h1>
                <p class="text-muted small mb-0">{{ $unreadCount }} unread</p>
            </div>
            @if ($unreadCount)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <button class="btn btn-outline-ml btn-sm"><i class="bi bi-check2-all me-1"></i>Mark all as read</button>
                </form>
            @endif
        </div>

        <div class="card-ml">
            @forelse ($notifications as $n)
                @php
                    $d = $n->data;
                    $unread = $n->read_at === null;
                @endphp
                <div class="d-flex gap-3 align-items-start p-3 {{ $unread ? 'bg-ml-green-light' : '' }} notif-item">
                    <div class="fs-4">
                        @if (($d['status'] ?? '') === 'ready_for_pickup')
                            📦
                        @elseif (in_array($d['status'] ?? '', ['declined', 'cancelled']))
                            ⚠️
                        @elseif (($d['status'] ?? '') === 'placed')
                            🛎️
                        @elseif (($d['status'] ?? '') === 'completed')
                            ✅
                        @else
                            ℹ️
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between flex-wrap gap-1">
                            <strong class="{{ $unread ? '' : 'opacity-75' }}">{{ $d['title'] ?? 'Notification' }}</strong>
                            <small class="text-muted">{{ $n->created_at->diffForHumans() }}</small>
                        </div>
                        <div class="small {{ $unread ? '' : 'opacity-75' }}">
                            {{ $d['body'] ?? '' }}
                            @if (! empty($d['order_number']))
                                — <strong>{{ $d['order_number'] }}</strong>
                            @endif
                        </div>
                        @if (! empty($d['note']))
                            <div class="small text-muted fst-italic">{{ $d['note'] }}</div>
                        @endif
                        <div class="mt-2 d-flex gap-2 align-items-center">
                            @if (! empty($d['url']))
                                <a class="btn btn-sm {{ $unread ? 'btn-ml' : 'btn-outline-ml' }}" href="{{ $d['url'] }}">Open</a>
                            @endif
                            @if ($unread)
                                <form method="POST" action="{{ route('notifications.read', $n) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-link text-muted p-0">Mark as read</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty">
                    <i class="bi bi-bell"></i>
                    <strong>No notifications yet.</strong>
                    <p class="small mb-0">Order updates will appear here.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-4">{{ $notifications->links() }}</div>
    </div>
</section>
@endsection

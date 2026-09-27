{{-- Shared admin chrome: topbar + sticky header with sidebar toggle --}}
<div class="admin-topbar d-none d-md-block">
    <div class="d-flex justify-content-between flex-wrap gap-2">
        <div>
            <span class="me-3"><i class="bi bi-shield-lock-fill"></i> System Administration Portal</span>
            <span><i class="bi bi-hdd-network-fill"></i> Status: <strong>All Systems Operational</strong></span>
        </div>
        <span><i class="bi bi-clock-history"></i> Active Session: <strong>{{ auth()->user()->name }}</strong></span>
    </div>
</div>
<header class="admin-header">
    <div class="d-flex align-items-center gap-2">
        <button class="mobile-nav-toggle" id="adminNavToggle" type="button" aria-label="Toggle admin navigation"><i class="bi bi-list"></i></button>
        <h1 class="h4 serif-font">@yield('admin_title', 'Admin Console')</h1>
    </div>
    <div class="d-flex align-items-center gap-3">
        <a class="header-icon-btn position-relative" href="{{ route('notifications.index') }}" title="Notifications" aria-label="Notifications">
            <i class="bi bi-bell"></i>
            @php $unreadNotifications = auth()->user()->unreadNotifications()->count(); @endphp
            <span id="notif-count" class="badge rounded-pill bg-danger {{ $unreadNotifications ? '' : 'd-none' }}">{{ $unreadNotifications }}</span>
        </a>
        <div class="dropdown">
            <button class="profile-badge dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border:none;background:var(--primary-light)">
                @include('layouts.partials._avatar', ['user' => auth()->user(), 'size' => 28])
                <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                <li><a class="dropdown-item" href="{{ route('admin.profile.edit') }}"><i class="bi bi-person-gear me-2"></i>My Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

@if (session('success') || session('error') || $errors->any())
    <div class="px-3 px-md-4 pt-3">
        @include('layouts.partials.flash')
    </div>
@endif

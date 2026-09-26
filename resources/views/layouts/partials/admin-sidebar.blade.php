<div class="dash-sidebar card-ml p-3 sticky-top" style="top:90px">
    <div class="fw-bold small text-uppercase text-muted mb-2 px-2">Administration</div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}"><i class="bi bi-people"></i> Users</a>
        <a class="nav-link {{ request()->routeIs('admin.markets') ? 'active' : '' }}" href="{{ route('admin.markets') }}"><i class="bi bi-shop"></i> Markets</a>
        <a class="nav-link {{ request()->routeIs('admin.categories') ? 'active' : '' }}" href="{{ route('admin.categories') }}"><i class="bi bi-tags"></i> Categories</a>
        <a class="nav-link {{ request()->routeIs('admin.moderation') ? 'active' : '' }}" href="{{ route('admin.moderation') }}"><i class="bi bi-shield-exclamation"></i> Moderation</a>
        <a class="nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}" href="{{ route('admin.reports') }}"><i class="bi bi-graph-up"></i> Reports</a>
        <a class="nav-link {{ request()->routeIs('admin.announcements') ? 'active' : '' }}" href="{{ route('admin.announcements') }}"><i class="bi bi-megaphone"></i> Announcements</a>
        <hr class="my-2">
        <a class="nav-link" href="{{ route('home') }}"><i class="bi bi-box-arrow-up-right"></i> View public site</a>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button class="nav-link text-danger border-0 bg-transparent w-100 text-start"><i class="bi bi-box-arrow-right"></i> Logout</button>
        </form>
    </nav>
</div>

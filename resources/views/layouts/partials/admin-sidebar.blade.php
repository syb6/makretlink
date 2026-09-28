{{-- Dark admin sidebar — fixed off-canvas below lg, with backdrop --}}
<aside class="sidebar" id="adminSidebar">
    <div>
        <a href="{{ route('admin.dashboard') }}" class="sidebar-brand"><i class="bi bi-basket2-fill"></i>Market<span>Link</span></a>
        <div class="sidebar-user">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
            <div>
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-section-title">Core</li>
            <li><a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a></li>
            <li class="sidebar-section-title">Management</li>
            <li><a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users') ? 'active' : '' }}"><i class="bi bi-people"></i> <span>Users &amp; Farmers</span></a></li>
            <li><a href="{{ route('admin.markets') }}" class="{{ request()->routeIs('admin.markets') ? 'active' : '' }}"><i class="bi bi-shop"></i> <span>Markets</span></a></li>
            <li><a href="{{ route('admin.categories') }}" class="{{ request()->routeIs('admin.categories') ? 'active' : '' }}"><i class="bi bi-tags"></i> <span>Categories</span></a></li>
            <li class="sidebar-section-title">System</li>
            @php
                $unreadFeedback = \App\Models\FeedbackMessage::whereNull('read_at')->count();
            @endphp
            <li><a href="{{ route('admin.feedback') }}" class="{{ request()->routeIs('admin.feedback*') ? 'active' : '' }}"><i class="bi bi-inbox"></i> <span>Feedback</span>@if ($unreadFeedback)<span class="badge rounded-pill bg-danger ms-1">{{ $unreadFeedback }}</span>@endif</a></li>
            <li><a href="{{ route('admin.moderation') }}" class="{{ request()->routeIs('admin.moderation') ? 'active' : '' }}"><i class="bi bi-shield-exclamation"></i> <span>Moderation</span></a></li>
            <li><a href="{{ route('admin.reports') }}" class="{{ request()->routeIs('admin.reports') ? 'active' : '' }}"><i class="bi bi-bar-chart-line"></i> <span>Reports</span></a></li>
            <li><a href="{{ route('admin.announcements') }}" class="{{ request()->routeIs('admin.announcements') ? 'active' : '' }}"><i class="bi bi-megaphone"></i> <span>Announcements</span></a></li>
            <li><a href="{{ route('admin.profile.edit') }}" class="{{ request()->routeIs('admin.profile.*') ? 'active' : '' }}"><i class="bi bi-person-gear"></i> <span>My Profile</span></a></li>
        </ul>
    </div>
    <div class="sidebar-footer">
        <ul class="sidebar-menu">
            <li><a href="{{ route('home') }}"><i class="bi bi-house"></i> <span>View public site</span></a></li>
            <li>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="text-danger"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></button>
                </form>
            </li>
        </ul>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

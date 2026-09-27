{{-- Shared top chrome: info bar + brand header + olive nav rail --}}
<div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <span class="me-3"><i class="bi bi-geo-alt"></i> Local Farmers Markets Connection</span>
            <span class="d-none d-md-inline"><i class="bi bi-clock"></i> Reserve Online &bull; Pickup At Market</span>
        </div>
        <span><i class="bi bi-bag-heart"></i> Pre-Order Online &bull; Pay In-Person at Stall</span>
    </div>
</div>

<header class="main-header">
    <div class="container header-content">
        <a href="{{ route('home') }}" class="logo-brand"><i class="bi bi-basket2-fill"></i>Market<span>Link</span></a>
        <div class="header-actions">
            <button class="theme-toggle" id="themeToggle" type="button" title="Toggle dark mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars" id="themeIcon"></i>
            </button>
            @auth
                @if (auth()->user()->isCustomer())
                    <a class="cart-badge-btn" href="{{ route('cart.index') }}" title="Pre-order basket">
                        <i class="bi bi-bag-check-fill text-success fs-5"></i>
                        <span class="cart-text d-none d-sm-inline">Pre-Order Basket</span>
                        @php $cartCount = \App\Models\Cart::countFor(auth()->user()); @endphp
                        <span id="cart-count" class="cart-count {{ $cartCount ? '' : 'd-none' }}">
                            {{ $cartCount }}
                        </span>
                    </a>
                @endif
                <a class="header-icon-btn position-relative" href="{{ route('notifications.index') }}" title="Notifications" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                    @php $unreadNotifications = auth()->user()->unreadNotifications()->count(); @endphp
                    <span id="notif-count" class="badge rounded-pill bg-danger {{ $unreadNotifications ? '' : 'd-none' }}">{{ $unreadNotifications }}</span>
                </a>
                <div class="dropdown">
                    <button class="profile-badge dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border:none;background:var(--primary-light)">
                        @include('layouts.partials._avatar', ['user' => auth()->user(), 'size' => 28])
                        <span class="d-none d-sm-inline">{{ explode(' ', trim(auth()->user()->name))[0] }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        @if (auth()->user()->isCustomer())
                            <li><a class="dropdown-item" href="{{ route('customer.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('orders.index') }}"><i class="bi bi-receipt me-2"></i>My Pre-Orders</a></li>
                            <li><a class="dropdown-item" href="{{ route('favorites.index') }}"><i class="bi bi-heart me-2"></i>Favorites</a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2"></i>Profile</a></li>
                        @elseif (auth()->user()->isFarmer())
                            <li><a class="dropdown-item" href="{{ route('farmer.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Farmer Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('farmer.stalls.index') }}"><i class="bi bi-shop me-2"></i>My Stalls</a></li>
                            <li><a class="dropdown-item" href="{{ route('farmer.orders.index') }}"><i class="bi bi-cart-check me-2"></i>Pre-Orders</a></li>
                        @else
                            <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ auth()->user()->isAdmin() ? route('admin.logout') : route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-ml auth-btn"><i class="bi bi-person"></i> Log In</a>
                <a href="{{ route('register') }}" class="btn btn-ml auth-btn">Sign Up</a>
            @endauth
        </div>
    </div>
    <div class="nav-bar-wrapper">
        <div class="container nav-bar-container">
            <ul class="nav-menu" id="navMenu">
                <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a></li>
                <li><a href="{{ route('markets.index') }}" class="{{ request()->routeIs('markets.*') ? 'active' : '' }}">Markets</a></li>
                <li><a href="{{ route('farmers.index') }}" class="{{ request()->routeIs('farmers.*') ? 'active' : '' }}">Farmers</a></li>
                <li><a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">Products</a></li>
                <li class="d-md-none"><a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">About Us</a></li>
                <li class="d-md-none"><a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'active' : '' }}">Contact</a></li>
            </ul>
            <div class="d-flex align-items-center">
                <ul class="nav-menu d-none d-md-flex">
                    <li><a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">About Us</a></li>
                    <li><a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'active' : '' }}">Contact</a></li>
                </ul>
                <button class="mobile-toggle" id="mobileToggle" type="button" aria-label="Toggle navigation" aria-expanded="false" aria-controls="navMenu"><i class="bi bi-list"></i></button>
            </div>
        </div>
    </div>
</header>

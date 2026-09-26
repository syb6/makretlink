<nav class="navbar navbar-expand-lg sticky-top marketlink-navbar">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('home') }}">
            <span class="brand-leaf"><i class="bi bi-basket2-fill"></i></span> Market<span class="text-success">Link</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('markets.*') ? 'active' : '' }}" href="{{ route('markets.index') }}">Markets</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('farmers.index') ? 'active' : '' }}" href="{{ route('farmers.index') }}">Farmers</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}" href="{{ route('products.index') }}">Products</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">About Us</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Contact</a></li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item me-lg-1">
                    <button class="theme-toggle" id="themeToggle" type="button" title="Toggle dark mode" aria-label="Toggle dark mode">
                        <i class="bi bi-moon-stars" id="themeIcon"></i>
                    </button>
                </li>
                @auth
                    @if (auth()->user()->isCustomer())
                        <li class="nav-item me-lg-2">
                            <a class="nav-link position-relative" href="{{ route('cart.index') }}" title="Cart">
                                <i class="bi bi-cart3 fs-5"></i>
                                <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success {{ \App\Models\Cart::countFor(auth()->user()) ? '' : 'd-none' }}">
                                    {{ \App\Models\Cart::countFor(auth()->user()) }}
                                </span>
                            </a>
                        </li>
                    @endif
                    <li class="nav-item me-lg-1">
                        <a class="nav-link position-relative" href="{{ route('notifications.index') }}" title="Notifications">
                            <i class="bi bi-bell fs-5"></i>
                            @php $unreadNotifications = auth()->user()->unreadNotifications()->count(); @endphp
                            <span id="notif-count"
                                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ $unreadNotifications ? '' : 'd-none' }}">
                                {{ $unreadNotifications }}
                            </span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5"></i> {{ explode(' ', trim(auth()->user()->name))[0] }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            @if (auth()->user()->isCustomer())
                                <li><a class="dropdown-item" href="{{ route('customer.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="{{ route('orders.index') }}"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
                                <li><a class="dropdown-item" href="{{ route('favorites.index') }}"><i class="bi bi-heart me-2"></i>Favorites</a></li>
                            @elseif (auth()->user()->isFarmer())
                                <li><a class="dropdown-item" href="{{ route('farmer.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Farmer Dashboard</a></li>
                                <li><a class="dropdown-item" href="{{ route('farmer.stalls.index') }}"><i class="bi bi-shop me-2"></i>My Stalls</a></li>
                                <li><a class="dropdown-item" href="{{ route('farmer.orders.index') }}"><i class="bi bi-bag-check me-2"></i>Pre-Orders</a></li>
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
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Login</a></li>
                    <li class="nav-item">
                        <a class="btn btn-success btn-sm px-3 ms-lg-2" href="{{ route('register') }}">Join as Customer</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-success btn-sm px-3" href="{{ route('register', ['role' => 'farmer']) }}">Join as Farmer</a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

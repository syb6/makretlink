<footer class="marketlink-footer mt-auto py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-basket2-fill me-2"></i>Market<span class="text-success">Link</span></h5>
                <p class="small opacity-75">Connecting local farmers and customers. Pre-order fresh, seasonal produce and pick it up at your local market — no wasted trips.</p>
                <div class="d-flex gap-3 fs-5">
                    <a href="#" class="text-decoration-none"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-decoration-none"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-decoration-none"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="text-decoration-none"><i class="bi bi-youtube"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="fw-semibold mb-3">Explore</h6>
                <ul class="list-unstyled small d-grid gap-2">
                    <li><a href="{{ route('markets.index') }}">Markets</a></li>
                    <li><a href="{{ route('farmers.index') }}">Farmers</a></li>
                    <li><a href="{{ route('products.index') }}">Products</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="fw-semibold mb-3">Account</h6>
                <ul class="list-unstyled small d-grid gap-2">
                    <li><a href="{{ route('login') }}">Login</a></li>
                    <li><a href="{{ route('register') }}">Register</a></li>
                    <li><a href="{{ route('about') }}">About Us</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="fw-semibold mb-3">Contact</h6>
                <ul class="list-unstyled small d-grid gap-2 opacity-75">
                    <li><i class="bi bi-geo-alt me-2"></i>Green Valley Community Market, Main St</li>
                    <li><i class="bi bi-envelope me-2"></i>hello@marketlink.test</li>
                    <li><i class="bi bi-telephone me-2"></i>(555) 010-2233</li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary opacity-25 my-4">
        <p class="small mb-0 opacity-50">© {{ now()->year }} MarketLink — TECHWIZ 7 "eGreen Basket". Pre-orders are paid in person at pickup.</p>
    </div>
</footer>

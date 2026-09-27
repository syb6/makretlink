<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="{{ route('home') }}" class="logo-brand mb-3"><i class="bi bi-basket2-fill"></i>Market<span>Link</span></a>
                <p>Connecting local farmers and customers. Pre-order fresh, seasonal produce and pick it up at your local market — no wasted trips.</p>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" aria-label="X"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Explore</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('markets.index') }}">Markets</a></li>
                    <li><a href="{{ route('farmers.index') }}">Farmers</a></li>
                    <li><a href="{{ route('products.index') }}">Products</a></li>
                    <li><a href="{{ route('about') }}">About Us</a></li>
                    <li><a href="{{ route('contact') }}">Contact</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('login') }}">Login</a></li>
                    <li><a href="{{ route('register') }}">Sign Up</a></li>
                    <li><a href="{{ route('register', ['role' => 'farmer']) }}">Join as Farmer</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Stay in Touch</h4>
                <ul class="footer-contact">
                    <li><i class="bi bi-geo-alt"></i><a class="text-decoration-none" href="{{ route('home') }}">Green Valley Community Market, Main St</a></li>
                    <li><i class="bi bi-envelope"></i><a  class="text-decoration-none" href="mailto:hello@marketlink.test">hello@marketlink.test</a></li>
                    <li><i class="bi bi-telephone"></i><a href="tel:+15550102233" class="text-decoration-none">(555) 010-2233</a></li>
                </ul>
                <a href="{{ route('markets.index') }}" class="btn btn-ml btn-sm mt-2">Find a Market</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; {{ now()->year }} MarketLink — TECHWIZ 7 "eGreen Basket". Pre-orders are paid in person at pickup. All Rights Reserved.</p>
        </div>
    </div>
</footer>

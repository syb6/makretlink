import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-footer',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink],
  template: `
    <footer class="site-footer">
      <div class="container">
        <div class="footer-grid">
          <div class="footer-brand">
            <a routerLink="/" class="logo-brand mb-3"><i class="bi bi-basket2-fill"></i>Market<span>Link</span></a>
            <p>Connecting local farmers and customers. Pre-order fresh, seasonal produce and pick it up at your local market — no wasted trips.</p>
            <div class="footer-social">
              <a href="mailto:hello@marketlink.test" aria-label="Email MarketLink"><i class="bi bi-envelope"></i></a>
              <a routerLink="/markets" aria-label="Find a local market"><i class="bi bi-geo-alt"></i></a>
              <a routerLink="/products" aria-label="Browse products"><i class="bi bi-basket"></i></a>
            </div>
          </div>
          <div class="footer-col">
            <h4>Explore</h4>
            <ul class="footer-links">
              <li><a routerLink="/markets">Markets</a></li>
              <li><a routerLink="/farmers">Farmers</a></li>
              <li><a routerLink="/products">Products</a></li>
              <li><a routerLink="/about">About Us</a></li>
              <li><a routerLink="/contact">Contact</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>Account</h4>
            <ul class="footer-links">
              <li><a routerLink="/login">Login</a></li>
              <li><a routerLink="/register">Sign Up</a></li>
              <li><a routerLink="/register" [queryParams]="{ role: 'farmer' }">Join as Farmer</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>Stay in Touch</h4>
            <ul class="footer-contact">
              <li><i class="bi bi-geo-alt"></i><a routerLink="/markets">Green Valley Community Market, University Road, Karachi</a></li>
              <li><i class="bi bi-envelope"></i><a href="mailto:hello@marketlink.test">hello@marketlink.test</a></li>
              <li><i class="bi bi-telephone"></i><a href="tel:+922111111111">+92 21 111 111 111</a></li>
            </ul>
            <a routerLink="/markets" class="btn btn-ml btn-sm mt-2">Find a Market</a>
          </div>
        </div>
        <div class="footer-bottom">
          <p>© {{ year }} MarketLink — TECHWIZ 7 “eGreen Basket”. Pre-orders are paid in person at pickup. All Rights Reserved.</p>
        </div>
      </div>
    </footer>
  `,
})
export class FooterComponent {
  readonly year = new Date().getFullYear();
}

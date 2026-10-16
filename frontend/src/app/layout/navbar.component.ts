import { ChangeDetectionStrategy, Component, OnDestroy, inject, OnInit } from '@angular/core';
import { Router, RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../core/auth.service';
import { PersonAvatarComponent } from '../core/person-avatar.component';
import { ToastService } from '../core/toast.service';
import { ConfirmService } from '../core/confirm.service';

@Component({
  selector: 'app-navbar',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, RouterLinkActive, PersonAvatarComponent],
  template: `
    <div class="top-bar">
      <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <span class="me-3"><i class="bi bi-geo-alt"></i>Local Farmers Markets Connection</span>
          <span class="d-none d-md-inline"><i class="bi bi-clock"></i>Reserve Online · Pickup At Market</span>
        </div>
        <span><i class="bi bi-bag-heart"></i>Pre-Order Online · Pay In-Person at Stall</span>
      </div>
    </div>

    <header class="main-header">
      <div class="container header-content">
        <a routerLink="/" class="logo-brand">
          <i class="bi bi-basket2-fill"></i>Market<span>Link</span>
        </a>

        <div class="header-actions">
          <button class="theme-toggle" type="button" (click)="toggleTheme()"
                  [attr.aria-label]="theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
                  title="Toggle dark mode">
            <i class="bi" [class.bi-sun]="theme === 'dark'" [class.bi-moon-stars]="theme === 'light'"></i>
          </button>

          @if (auth.user(); as u) {
            @if (auth.isCustomer()) {
              <a class="cart-badge-btn" routerLink="/customer/cart" title="Cart">
                <i class="bi bi-bag-check-fill text-success fs-5"></i>
                <span class="cart-text d-none d-sm-inline">Cart</span>
                @if (auth.cartCount() > 0) { <span class="cart-count">{{ auth.cartCount() }}</span> }
              </a>
            }
            <a class="header-icon-btn" routerLink="/notifications" title="Notifications" aria-label="Notifications">
              <i class="bi bi-bell"></i>
              @if (auth.unread() > 0) { <span class="badge rounded-pill bg-danger">{{ auth.unread() }}</span> }
            </a>
            <div class="dropdown">
              <button class="profile-badge dropdown-toggle" type="button" style="border:none;background:var(--primary-light)"
                      (click)="userMenu = !userMenu" [attr.aria-expanded]="userMenu">
                <app-person-avatar [name]="u.name" [src]="u.avatar" class="avatar-sm rounded-circle" />
                <span class="d-none d-lg-inline">{{ firstName(u.name) }}</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow" [class.show]="userMenu">
                @if (auth.isCustomer()) {
                  <li><a class="dropdown-item" routerLink="/customer/dashboard" (click)="userMenu = false"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                  <li><a class="dropdown-item" routerLink="/customer/orders" (click)="userMenu = false"><i class="bi bi-receipt me-2"></i>My Pre-Orders</a></li>
                  <li><a class="dropdown-item" routerLink="/customer/favorites" (click)="userMenu = false"><i class="bi bi-heart me-2"></i>Favorites</a></li>
                  <li><a class="dropdown-item" routerLink="/customer/profile" (click)="userMenu = false"><i class="bi bi-person-gear me-2"></i>Profile</a></li>
                } @else if (auth.isFarmer()) {
                  <li><a class="dropdown-item" routerLink="/farmer/dashboard" (click)="userMenu = false"><i class="bi bi-speedometer2 me-2"></i>Farmer Dashboard</a></li>
                  <li><a class="dropdown-item" routerLink="/farmer/stalls" (click)="userMenu = false"><i class="bi bi-shop me-2"></i>My Stalls</a></li>
                  <li><a class="dropdown-item" routerLink="/farmer/orders" (click)="userMenu = false"><i class="bi bi-cart-check me-2"></i>Pre-Orders</a></li>
                } @else if (auth.isAdmin()) {
                  <li><a class="dropdown-item" routerLink="/admin/dashboard" (click)="userMenu = false"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
                }
                <li><hr class="dropdown-divider"></li>
                <li><button class="dropdown-item" type="button" (click)="logout()"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></li>
              </ul>
            </div>
          } @else {
            <a routerLink="/login" class="btn btn-outline-ml auth-btn"><i class="bi bi-person"></i> Log In</a>
            <a routerLink="/register" class="btn btn-ml auth-btn">Sign Up</a>
          }
        </div>
      </div>

      <div class="nav-bar-wrapper">
        <div class="container nav-bar-container">
          <ul class="nav-menu" [class.open]="open">
            <li><a routerLink="/" routerLinkActive="active" [routerLinkActiveOptions]="{ exact: true }" (click)="open = false">Home</a></li>
            <li><a routerLink="/markets" routerLinkActive="active" (click)="open = false">Markets</a></li>
            <li><a routerLink="/farmers" routerLinkActive="active" (click)="open = false">Farmers</a></li>
            <li><a routerLink="/products" routerLinkActive="active" (click)="open = false">Products</a></li>
            <li class="d-md-none"><a routerLink="/about" routerLinkActive="active" (click)="open = false">About Us</a></li>
            <li class="d-md-none"><a routerLink="/contact" routerLinkActive="active" (click)="open = false">Contact</a></li>
          </ul>
          <div class="d-flex align-items-center">
            <ul class="nav-menu d-none d-md-flex">
              <li><a routerLink="/about" routerLinkActive="active">About Us</a></li>
              <li><a routerLink="/contact" routerLinkActive="active">Contact</a></li>
            </ul>
            <button class="mobile-toggle" type="button" (click)="open = !open"
                    aria-label="Toggle navigation" [attr.aria-expanded]="open">
              <i class="bi" [class.bi-list]="!open" [class.bi-x-lg]="open"></i>
            </button>
          </div>
        </div>
      </div>
    </header>
  `,
  styles: `
    .avatar-sm { width: 28px; height: 28px; object-fit: cover; }
  `,
})
export class NavbarComponent implements OnInit, OnDestroy {
  readonly auth = inject(AuthService);
  private router = inject(Router);
  private toast = inject(ToastService);
  private confirm = inject(ConfirmService);

  open = false;
  userMenu = false;
  theme: 'light' | 'dark' = 'light';
  private pollTimer: ReturnType<typeof setInterval> | null = null;

  ngOnInit(): void {
    this.theme = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
    // Refresh the notification badge every 30s while the tab is visible,
    // matching the @auth script in layouts/app.blade.php.
    this.pollTimer = setInterval(() => {
      if (document.hidden || !this.auth.user()) return;
      void this.auth.load();
    }, 30000);
  }

  ngOnDestroy(): void {
    if (this.pollTimer) clearInterval(this.pollTimer);
  }

  firstName(name: string): string {
    return name.trim().split(/\s+/)[0] ?? name;
  }

  toggleTheme(): void {
    this.theme = this.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.classList.add('theme-anim');
    document.documentElement.setAttribute('data-bs-theme', this.theme);
    localStorage.setItem('ml-theme', this.theme);
    window.setTimeout(() => document.documentElement.classList.remove('theme-anim'), 450);
  }

  async logout(): Promise<void> {
    this.userMenu = false;
    const confirmed = await this.confirm.confirm('Sign out of MarketLink?', {
      title: 'See you soon!',
      danger: false,
      okLabel: 'Sign out',
    });
    if (!confirmed) return;
    try {
      await this.auth.logout();
      await this.router.navigate(['/']);
    } catch (error) {
      this.toast.fromValidation(error);
    }
  }
}

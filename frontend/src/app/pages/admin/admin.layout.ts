import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { NavigationEnd, Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { filter } from 'rxjs';
import { AuthService } from '../../core/auth.service';
import { PersonAvatarComponent } from '../../core/person-avatar.component';
import { ToastService } from '../../core/toast.service';

@Component({
  selector: 'app-admin-layout',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, PersonAvatarComponent],
  template: `
    <div class="app-container admin-shell">
      <aside class="sidebar" [class.show]="navOpen()">
        <div>
          <a routerLink="/admin/dashboard" class="sidebar-brand" (click)="closeMenus()">
            <i class="bi bi-basket2-fill"></i>Market<span>Link</span>
          </a>
          <div class="sidebar-user">
            <div class="user-avatar">{{ initials() }}</div>
            <div>
              <div class="user-name">{{ auth.user()?.name ?? 'Administrator' }}</div>
              <div class="user-role">Administrator</div>
            </div>
          </div>
          <ul class="sidebar-menu">
            <li class="sidebar-section-title">Core</li>
            <li><a routerLink="/admin/dashboard" routerLinkActive="active" [routerLinkActiveOptions]="{ exact: true }" (click)="closeMenus()"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
            <li class="sidebar-section-title">Management</li>
            <li><a routerLink="/admin/users" routerLinkActive="active" (click)="closeMenus()"><i class="bi bi-people"></i><span>Users &amp; Farmers</span></a></li>
            <li><a routerLink="/admin/markets" routerLinkActive="active" (click)="closeMenus()"><i class="bi bi-shop"></i><span>Markets</span></a></li>
            <li><a routerLink="/admin/categories" routerLinkActive="active" (click)="closeMenus()"><i class="bi bi-tags"></i><span>Categories</span></a></li>
            <li class="sidebar-section-title">System</li>
            <li><a routerLink="/admin/feedback" routerLinkActive="active" (click)="closeMenus()"><i class="bi bi-inbox"></i><span>Feedback</span></a></li>
            <li><a routerLink="/admin/moderation" routerLinkActive="active" (click)="closeMenus()"><i class="bi bi-shield-exclamation"></i><span>Moderation</span></a></li>
            <li><a routerLink="/admin/reports" routerLinkActive="active" (click)="closeMenus()"><i class="bi bi-bar-chart-line"></i><span>Reports</span></a></li>
            <li><a routerLink="/admin/announcements" routerLinkActive="active" (click)="closeMenus()"><i class="bi bi-megaphone"></i><span>Announcements</span></a></li>
            <li><a routerLink="/admin/profile" routerLinkActive="active" (click)="closeMenus()"><i class="bi bi-person-gear"></i><span>My Profile</span></a></li>
          </ul>
        </div>
        <div class="sidebar-footer">
          <ul class="sidebar-menu">
            <li><a routerLink="/" (click)="closeMenus()"><i class="bi bi-house"></i><span>View public site</span></a></li>
            <li><button type="button" class="text-danger" (click)="logout()"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button></li>
          </ul>
        </div>
      </aside>

      @if (navOpen()) {
        <button type="button" class="sidebar-backdrop show" aria-label="Close admin navigation" (click)="closeMenus()"></button>
      }

      <div class="main-wrapper">
        <div class="admin-topbar d-none d-md-block">
          <div class="d-flex justify-content-between flex-wrap gap-2">
            <span><i class="bi bi-shield-lock-fill"></i>System Administration Portal</span>
            <span><i class="bi bi-clock-history"></i>Active Session: <strong>{{ auth.user()?.name ?? 'Administrator' }}</strong></span>
          </div>
        </div>
        <header class="admin-header">
          <div class="d-flex align-items-center gap-2">
            <button class="mobile-nav-toggle" type="button" aria-label="Toggle admin navigation"
                    [attr.aria-expanded]="navOpen()" (click)="toggleNav()">
              <i class="bi bi-list"></i>
            </button>
            <h1 class="h4 serif-font mb-0">{{ pageTitle() }}</h1>
          </div>
          <div class="d-flex align-items-center gap-3">
            <button class="theme-toggle" type="button" (click)="toggleTheme()"
                    [attr.aria-label]="theme() === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'">
              <i class="bi" [class.bi-sun]="theme() === 'dark'" [class.bi-moon-stars]="theme() === 'light'"></i>
            </button>
            <a class="header-icon-btn position-relative" routerLink="/notifications" title="Notifications" aria-label="Notifications">
              <i class="bi bi-bell"></i>
              @if (auth.unread() > 0) { <span class="badge rounded-pill bg-danger">{{ auth.unread() }}</span> }
            </a>
            <div class="dropdown">
              <button class="profile-badge dropdown-toggle border-0" type="button"
                      [attr.aria-expanded]="userMenu()" (click)="toggleUserMenu()">
                <app-person-avatar [name]="auth.user()?.name ?? 'Administrator'"
                                   [src]="auth.user()?.avatar"
                                   class="admin-header-avatar rounded-circle" />
                <span class="d-none d-sm-inline">{{ auth.user()?.name ?? 'Administrator' }}</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow" [class.show]="userMenu()">
                <li><a class="dropdown-item" routerLink="/admin/dashboard" (click)="userMenu.set(false)"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                <li><a class="dropdown-item" routerLink="/admin/profile" (click)="userMenu.set(false)"><i class="bi bi-person-gear me-2"></i>My Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><button class="dropdown-item" type="button" (click)="logout()"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></li>
              </ul>
            </div>
          </div>
        </header>
        <main class="p-3 p-md-4 admin-page-content">
          <router-outlet />
        </main>
        <footer class="admin-footer">
          <div class="container text-center">
            <p class="text-muted small mb-1">System Administration Portal &bull; Connecting Local Farmers &amp; Community Customers</p>
            <p class="text-muted small mb-0">&copy; {{ year }} MarketLink. All Rights Reserved. Built for TechWiz 7.</p>
          </div>
        </footer>
      </div>
    </div>
  `,
  styles: `
    .admin-header-avatar { width: 28px; height: 28px; font-size: .7rem; }
    .admin-page-content { flex: 1 0 auto; width: 100%; }
    .admin-header .profile-badge { display: flex; align-items: center; gap: .5rem; }
    .sidebar-backdrop { width: 100%; height: 100%; padding: 0; border: 0; }
  `,
})
export class AdminLayoutComponent implements OnInit {
  readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  readonly navOpen = signal(false);
  readonly userMenu = signal(false);
  readonly theme = signal<'light' | 'dark'>('light');
  readonly pageTitle = signal('Admin Dashboard');
  readonly year = new Date().getFullYear();

  constructor() {
    this.setPageTitle(this.router.url);
    this.router.events
      .pipe(filter((event): event is NavigationEnd => event instanceof NavigationEnd))
      .subscribe((event) => {
        this.setPageTitle(event.urlAfterRedirects);
        this.closeMenus();
      });
  }

  ngOnInit(): void {
    this.theme.set(document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light');
  }

  initials(): string {
    return (this.auth.user()?.name ?? 'Administrator')
      .trim()
      .split(/\s+/)
      .slice(0, 2)
      .map((part) => part.charAt(0))
      .join('')
      .toUpperCase();
  }

  toggleTheme(): void {
    const next = this.theme() === 'dark' ? 'light' : 'dark';
    this.theme.set(next);
    document.documentElement.classList.add('theme-anim');
    document.documentElement.setAttribute('data-bs-theme', next);
    localStorage.setItem('ml-theme', next);
    window.setTimeout(() => document.documentElement.classList.remove('theme-anim'), 450);
  }

  closeMenus(): void {
    this.navOpen.set(false);
    this.userMenu.set(false);
  }

  toggleNav(): void {
    this.navOpen.update((open) => !open);
  }

  toggleUserMenu(): void {
    this.userMenu.update((open) => !open);
  }

  logout(): void {
    this.closeMenus();
    void this.auth.logout()
      .then(() => this.router.navigateByUrl('/'))
      .catch((error: unknown) => this.toast.fromValidation(error));
  }

  private setPageTitle(url: string): void {
    const segment = url.split(/[/?#]/)[2] ?? 'dashboard';
    const titles: Record<string, string> = {
      dashboard: 'Admin Dashboard',
      profile: 'My Profile',
      users: 'Users & Farmers',
      markets: 'Markets',
      categories: 'Categories',
      feedback: 'Feedback',
      moderation: 'Moderation',
      reports: 'Reports',
      announcements: 'Announcements',
    };
    this.pageTitle.set(titles[segment] ?? 'Admin Console');
  }
}

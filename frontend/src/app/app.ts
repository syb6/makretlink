import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { NavigationEnd, Router, RouterOutlet } from '@angular/router';
import { filter } from 'rxjs';
import { AuthService } from './core/auth.service';
import { ApiService } from './core/api.service';
import { NavbarComponent } from './layout/navbar.component';
import { FooterComponent } from './layout/footer.component';
import { ToastsComponent } from './layout/toasts.component';

@Component({
  selector: 'app-root',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterOutlet, NavbarComponent, FooterComponent, ToastsComponent],
  template: `
    @if (api.activeRequests() > 0) {
      <div class="api-progress" role="progressbar" aria-label="Loading" aria-valuetext="Loading">
        <span></span>
      </div>
    }
    @if (!adminArea()) { <app-navbar /> }
    @if (api.lastFailure(); as failure) {
      <div class="api-error-banner" role="alert">
        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
        <div class="api-error-copy">
          <strong>MarketLink couldn't complete that request.</strong>
          <span>{{ failure.displayMessage }}</span>
        </div>
        <div class="d-flex gap-2 ms-auto">
          <button type="button" class="btn btn-sm btn-ml" (click)="reloadPage()">Reload this page</button>
          <button type="button" class="btn-close" aria-label="Dismiss request error" (click)="dismissFailure()"></button>
        </div>
      </div>
    }
    @if (!adminArea()) {
      @if (!online()) {
        <div class="offline-banner show" role="status" aria-live="polite">
          <i class="bi bi-wifi-off" aria-hidden="true"></i>
          <span>You appear to be offline — actions won't go through until you reconnect.</span>
        </div>
      } @else if (backOnline()) {
        <div class="offline-banner online show" role="status" aria-live="polite">
          <i class="bi bi-check-circle" aria-hidden="true"></i>
          <span>Back online — you're good to go.</span>
        </div>
      }
    }
    <main id="main" class="sp-shell" tabindex="-1">
      <router-outlet />
    </main>
    @if (!adminArea()) { <app-footer /> }
    <app-toasts />
  `,
})
export class App {
  private auth = inject(AuthService);
  private router = inject(Router);
  readonly api = inject(ApiService);
  online = signal(navigator.onLine);
  backOnline = signal(false);
  adminArea = signal(this.router.url.startsWith('/admin'));

  constructor() {
    void this.auth.load();
    window.addEventListener('offline', () => {
      this.online.set(false);
      this.backOnline.set(false);
    });
    window.addEventListener('online', () => {
      this.online.set(true);
      this.backOnline.set(true);
      window.setTimeout(() => this.backOnline.set(false), 3000);
    });
    this.router.events
      .pipe(filter((event): event is NavigationEnd => event instanceof NavigationEnd))
      .subscribe((event) => {
        this.adminArea.set(event.urlAfterRedirects.startsWith('/admin'));
      });
  }

  reloadPage(): void {
    window.location.reload();
  }

  dismissFailure(): void {
    this.api.clearFailure();
  }
}

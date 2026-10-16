import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { NavigationEnd, Router, RouterOutlet } from '@angular/router';
import { filter } from 'rxjs';
import { AuthService } from './core/auth.service';
import { ApiService } from './core/api.service';
import { MotionService } from './core/motion.service';
import { NavbarComponent } from './layout/navbar.component';
import { FooterComponent } from './layout/footer.component';
import { ToastsComponent } from './layout/toasts.component';
import { BackToTopComponent } from './layout/back-to-top.component';
import { ChatbotComponent } from './layout/chatbot.component';

@Component({
  selector: 'app-root',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterOutlet, NavbarComponent, FooterComponent, ToastsComponent, BackToTopComponent, ChatbotComponent],
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
    @if (showChatbot()) { <app-chatbot /> }
    <app-toasts />
    <app-back-to-top />
  `,
})
export class App implements OnInit {
  private auth = inject(AuthService);
  private router = inject(Router);
  readonly api = inject(ApiService);
  private motion = inject(MotionService);

  online = signal(navigator.onLine);
  backOnline = signal(false);
  adminArea = signal(this.router.url.startsWith('/admin'));
  isAuthPage = signal(false);

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
        const url = event.urlAfterRedirects;
        this.adminArea.set(url.startsWith('/admin'));
        // Blade hides the assistant on login/register/password/* pages.
        this.isAuthPage.set(/^\/(login|register|forgot-password|reset-password)/.test(url));
      });
  }

  ngOnInit(): void {
    this.motion.start();
  }

  /** Same visibility rule as the Blade layout: hidden on admin and auth routes. */
  showChatbot(): boolean {
    return !this.adminArea() && !this.isAuthPage();
  }

  reloadPage(): void {
    window.location.reload();
  }

  dismissFailure(): void {
    this.api.clearFailure();
  }
}

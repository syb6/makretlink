import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth.service';
import { ToastService } from '../../core/toast.service';

@Component({
  selector: 'app-login',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule, RouterLink],
  template: `
    <div class="auth-main">
      <div class="auth-container">
        <div class="auth-card">
          <div class="text-center mb-4">
            <a routerLink="/" class="logo-brand justify-content-center mb-3"><i class="bi bi-basket2-fill"></i>Market<span>Link</span></a>
            <h1 class="h3 mb-2">Welcome Back</h1>
            <p class="text-muted small mb-0">Log in to manage your stall or view your pre-orders.</p>
          </div>

          <form (ngSubmit)="submit()" name="loginForm" novalidate>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input id="email" type="email" name="email" class="form-control"
                       [(ngModel)]="email" required placeholder="name@example.com">
              </div>
            </div>

            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="password" class="form-label mb-0">Password</label>
              </div>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input id="password" type="password" name="password" class="form-control"
                       [(ngModel)]="password" required placeholder="Enter your password">
              </div>
              <div class="text-end mt-1">
                <a routerLink="/forgot-password" class="small text-decoration-none fw-semibold auth-minilink">Forgot password?</a>
              </div>
            </div>

            <div class="form-check mb-4">
              <input class="form-check-input" type="checkbox" name="remember" id="remember" [(ngModel)]="remember">
              <label class="form-check-label small text-muted" for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn btn-ml w-100 py-2" [disabled]="busy()">
              @if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }
              <i class="bi bi-box-arrow-in-right me-1"></i> Log In
            </button>
          </form>

          <div class="text-center mt-4 small">
            <span class="text-muted">New to MarketLink?</span>
            <a routerLink="/register" class="fw-semibold text-decoration-none auth-minilink">Create an account</a>
          </div>

          <hr class="my-4">
          <div class="text-center small text-muted">
            &copy; {{ year }} MarketLink. All rights reserved.
          </div>
        </div>
      </div>
    </div>
  `,
})
export class LoginComponent {
  private auth = inject(AuthService);
  private toast = inject(ToastService);
  private router = inject(Router);

  email = '';
  password = '';
  remember = false;
  busy = signal(false);
  readonly year = new Date().getFullYear();

  async submit(): Promise<void> {
    this.busy.set(true);
    try {
      const res = await this.auth.login(this.email, this.password, this.remember);
      this.router.navigateByUrl(res?.redirect ?? '/');
    } catch (e: unknown) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }
}

import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';

interface ResetEmailResult {
  ok?: boolean;
  flash?: Record<string, string>;
  redirect?: string;
}

@Component({
  selector: 'app-forgot-password',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule, RouterLink],
  template: `
    <section class="py-5">
      <div class="auth-container">
        <div class="auth-card">
          <div class="text-center mb-4">
            <div class="auth-icon mb-3"><i class="bi bi-key"></i></div>
            <h1 class="section-title mb-1">Forgot your password?</h1>
            <p class="text-muted small mb-0">
              Enter your account email and we'll send you a secure reset link.
              Links expire after <strong>30 minutes</strong> and work only once.
            </p>
          </div>

          @if (status()) {
            <div class="ml-alert success" role="status" data-ml-alert data-ttl="8000">
              <div class="ml-alert-icon"><i class="bi bi-check-circle-fill"></i></div>
              <div class="ml-alert-body">
                <div class="ml-alert-msg">
                  <i class="bi bi-envelope-check me-1"></i> {{ status() }}
                  <div class="small mt-1 opacity-75">The link <strong>expires after 30 minutes</strong> and can be used only once.</div>
                </div>
              </div>
              <button type="button" class="ml-alert-close" aria-label="Dismiss" (click)="status.set(null)"><i class="bi bi-x-lg"></i></button>
              <span class="ml-alert-progress" aria-hidden="true" style="--ttl: 8s"></span>
            </div>
          }

          @if (devUrl()) {
            <div class="dev-reset-link">
              <div class="fw-semibold mb-1"><i class="bi bi-terminal me-1"></i>Local mail preview — your reset link (valid until {{ expiry() }}):</div>
              <a [href]="devUrl()" class="dev-reset-url">{{ devUrl() }}</a>
            </div>
          }

          <form (ngSubmit)="submit()" novalidate>
            <div class="mb-3">
              <label class="form-label fw-semibold" for="email">Email address</label>
              <input id="email" type="email" name="email" class="form-control"
                     [(ngModel)]="email" required placeholder="name@example.com">
            </div>
            <button type="submit" class="btn btn-ml w-100 mb-3" [disabled]="busy()">
              @if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }
              <i class="bi bi-send me-1"></i> Send reset link
            </button>
            <div class="text-center small">
              <a routerLink="/login" class="fw-semibold text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a>
            </div>
          </form>
        </div>
      </div>
    </section>
  `,
})
export class ForgotPasswordComponent {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  email = '';
  busy = signal(false);
  status = signal<string | null>(null);
  devUrl = signal<string | null>(null);
  expiry = signal('');

  async submit(): Promise<void> {
    this.busy.set(true);
    this.devUrl.set(null);
    try {
      const res = await this.api.form<ResetEmailResult>('/password/email', { email: this.email });
      this.status.set(res.flash?.['success'] ?? 'We emailed you a password reset link!');
      if (res.flash?.['reset-url']) {
        this.devUrl.set(res.flash['reset-url']);
        this.expiry.set(res.flash['reset-expiry'] ?? '');
      }
    } catch (e: unknown) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }
}

import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';

@Component({
  selector: 'app-forgot-password',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule, RouterLink],
  template: `
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h2 class="fw-bold mb-1">Forgot password</h2>
            <p class="text-muted small mb-4">We'll email a reset link — valid for 30 minutes, single use.</p>

            @if (resetUrl()) {
              <div class="alert alert-info small">
                Dev mail driver detected — your reset link:<br>
                <a [href]="resetUrl()">Open reset page</a>
                <div class="text-secondary mt-1">Expires: {{ expiry() }}</div>
              </div>
            }

            <form (ngSubmit)="submit()" autocomplete="off">
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="email" [(ngModel)]="email" required>
              </div>
              <button class="btn btn-success w-100" [disabled]="busy()">@if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }Email reset link</button>
            </form>

            <p class="text-center small text-muted mt-3 mb-0"><a routerLink="/login">Back to login</a></p>
          </div>
        </div>
      </div>
    </div>
  `,
})
export class ForgotPasswordComponent {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  email = '';
  busy = signal(false);
  resetUrl = signal<string | null>(null);
  expiry = signal('');

  async submit(): Promise<void> {
    this.busy.set(true);
    this.resetUrl.set(null);
    try {
      const res = await this.api.form<{ flash: Record<string, string> }>('/password/email', { email: this.email });
      this.toast.fromFlash(res.flash);
      if (res.flash?.['reset-url']) {
        this.resetUrl.set(String(res.flash['reset-url']));
        this.expiry.set(String(res.flash['reset-expiry'] ?? ''));
      }
    } catch (e) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }
}

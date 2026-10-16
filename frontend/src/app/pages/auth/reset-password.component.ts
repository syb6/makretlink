import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../core/auth.service';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';

@Component({
  selector: 'app-reset-password',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule],
  template: `
    <section class="py-5">
      <div class="auth-container">
        <div class="auth-card">
          <div class="text-center mb-4">
            <div class="auth-icon mb-3"><i class="bi bi-shield-lock"></i></div>
            <h1 class="section-title mb-1">Choose a new password</h1>
            <p class="text-muted small mb-0">
              <i class="bi bi-hourglass-split me-1"></i>Reset links expire <strong>30 minutes</strong>
              after they are sent and work only once.
            </p>
          </div>

          <form (ngSubmit)="submit()" novalidate>
            <div class="mb-3">
              <label class="form-label fw-semibold" for="email">Email address</label>
              <input id="email" type="email" name="email" class="form-control"
                     [(ngModel)]="email" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" for="password">New password</label>
              <input id="password" type="password" name="password" class="form-control"
                     [(ngModel)]="password" required autocomplete="new-password"
                     placeholder="At least 8 characters">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" for="password_confirmation">Repeat new password</label>
              <input id="password_confirmation" type="password" name="password_confirmation" class="form-control"
                     [(ngModel)]="passwordConfirmation" required autocomplete="new-password"
                     placeholder="Same password again">
            </div>

            <button type="submit" class="btn btn-ml w-100 mb-3" [disabled]="busy()">
              @if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }
              <i class="bi bi-check2-circle me-1"></i> Change password
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
export class ResetPasswordComponent {
  private api = inject(ApiService);
  private auth = inject(AuthService);
  private toast = inject(ToastService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  email = this.route.snapshot.queryParamMap.get('email') ?? '';
  password = '';
  passwordConfirmation = '';
  busy = signal(false);

  async submit(): Promise<void> {
    if (this.password !== this.passwordConfirmation) {
      this.toast.error('Passwords do not match.');
      return;
    }
    this.busy.set(true);
    try {
      const res = await this.api.form<{ flash: Record<string, string>; redirect: string }>('/password/reset', {
        token: this.route.snapshot.paramMap.get('token'),
        email: this.email,
        password: this.password,
        password_confirmation: this.passwordConfirmation,
      });
      this.toast.fromFlash(res.flash);
      await this.auth.load();
      this.router.navigateByUrl(res.redirect || '/');
    } catch (e) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }
}

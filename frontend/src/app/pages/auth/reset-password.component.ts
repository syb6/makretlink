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
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h2 class="fw-bold mb-1">Set a new password</h2>
            <p class="text-muted small mb-4">You'll be signed in automatically afterwards.</p>

            <form (ngSubmit)="submit()" autocomplete="off">
              <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" [(ngModel)]="email" required></div>
              <div class="mb-3"><label class="form-label">New password</label><input class="form-control" type="password" name="password" [(ngModel)]="password" required minlength="8"></div>
              <div class="mb-3"><label class="form-label">Confirm new password</label><input class="form-control" type="password" name="password_confirmation" [(ngModel)]="passwordConfirmation" required minlength="8"></div>
              <button class="btn btn-success w-100" [disabled]="busy()">@if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }Change password</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  `,
})
export class ResetPasswordComponent {
  private api = inject(ApiService);
  private auth = inject(AuthService);
  private toast = inject(ToastService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  email = (this.route.snapshot.queryParamMap.get('email') ?? '');
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

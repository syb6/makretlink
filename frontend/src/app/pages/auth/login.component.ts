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
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h2 class="fw-bold mb-1">Welcome back</h2>
            <p class="text-muted small mb-4">Sign in to pre-order and track your orders.</p>

            <form (ngSubmit)="submit()" autocomplete="off">
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="email" [(ngModel)]="email" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input class="form-control" type="password" name="password" [(ngModel)]="password" required>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="remember" [(ngModel)]="remember" id="rememberChk">
                  <label class="form-check-label small" for="rememberChk">Remember me</label>
                </div>
                <a routerLink="/forgot-password" class="small">Forgot password?</a>
              </div>
              <button class="btn btn-success w-100" [disabled]="busy()">@if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }Sign in</button>
            </form>

            <p class="text-center small text-muted mt-3 mb-0">New here? <a routerLink="/register">Create an account</a></p>
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

  async submit(): Promise<void> {
    this.busy.set(true);
    try {
      const res = await this.auth.login(this.email, this.password, this.remember);
      this.router.navigateByUrl(res?.redirect ?? '/');
    } catch (e: any) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }
}

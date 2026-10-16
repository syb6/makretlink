import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { ActivatedRoute } from '@angular/router';
import { AuthService } from '../../core/auth.service';
import { ToastService } from '../../core/toast.service';

@Component({
  selector: 'app-register',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule, RouterLink],
  template: `
    <div class="auth-main">
      <div class="auth-container wide">
        <div class="auth-card">
          <div class="text-center mb-4">
            <a routerLink="/" class="logo-brand justify-content-center mb-3"><i class="bi bi-basket2-fill"></i>Market<span>Link</span></a>
            <h1 class="h3 mb-2">Create Your Account</h1>
            <p class="text-muted small mb-0">Join as a customer or farmer to get started on MarketLink.</p>
          </div>

          <form (ngSubmit)="submit()" id="registerForm" novalidate>
            <label class="form-label d-block mb-2">I want to join as</label>
            <div class="role-selector-wrapper">
              <div>
                <input type="radio" class="role-btn-input" name="roleRadio" id="roleCustomer"
                       [checked]="role() === 'customer'" (change)="role.set('customer')">
                <label class="role-btn-label" for="roleCustomer">
                  <i class="bi bi-bag-heart"></i>
                  <span>Customer</span>
                  <small>Pre-order fresh local items</small>
                </label>
              </div>
              <div>
                <input type="radio" class="role-btn-input" name="roleRadio" id="roleFarmer"
                       [checked]="role() === 'farmer'" (change)="role.set('farmer')">
                <label class="role-btn-label" for="roleFarmer">
                  <i class="bi bi-shop"></i>
                  <span>Farmer</span>
                  <small>List produce &amp; manage stall</small>
                </label>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="fullName" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="fullName" name="name"
                       [(ngModel)]="f.name" required placeholder="e.g. Jane Smith">
              </div>
              <div class="col-md-6 mb-3">
                <label for="phoneNumber" class="form-label">Phone</label>
                <input type="tel" class="form-control" id="phoneNumber" name="phone"
                       [(ngModel)]="f.phone" required placeholder="+92 3XX XXXXXXX">
              </div>
            </div>

            <div class="mb-3">
              <label for="emailAddress" class="form-label">Email Address</label>
              <input type="email" class="form-control" id="emailAddress" name="email"
                     [(ngModel)]="f.email" required placeholder="name@example.com">
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="accountPassword" class="form-label">Password</label>
                <input type="password" class="form-control" id="accountPassword" name="password"
                       [(ngModel)]="f.password" required placeholder="Create a password">
              </div>
              <div class="col-md-6 mb-3">
                <label for="passwordConfirmation" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="passwordConfirmation" name="password_confirmation"
                       [(ngModel)]="f.password_confirmation" required placeholder="Repeat your password">
              </div>
            </div>

            @if (roleIs('customer')) {
              <div class="role-fields role-customer">
                <label class="form-label">Delivery / contact address <span class="text-danger">*</span></label>
                <textarea name="address" rows="2" class="form-control" placeholder="Street, city..." [(ngModel)]="f.address"></textarea>
              </div>
            } @else {
              <div class="role-fields role-farmer">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Stall / business name <span class="text-danger">*</span></label>
                    <input type="text" name="business_name" class="form-control" [(ngModel)]="f.business_name">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Contact person <span class="optional-tag">(optional)</span></label>
                    <input type="text" name="contact_person" class="form-control" [(ngModel)]="f.contact_person">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Farm / business address</label>
                    <textarea name="business_address" rows="2" class="form-control" [(ngModel)]="f.business_address"></textarea>
                    <div class="form-text">Farmer accounts are reviewed by an administrator before you can list products.</div>
                  </div>
                </div>
              </div>
            }

            <div class="form-check mb-4 mt-2">
              <input class="form-check-input" type="checkbox" id="termsCheck" name="terms" required [(ngModel)]="terms">
              <label class="form-check-label small text-muted" for="termsCheck">
                I agree to MarketLink's
                <a routerLink="/about" class="text-dark text-decoration-underline">Terms of Service</a> and
                <a routerLink="/about" class="text-dark text-decoration-underline">Privacy Policy</a>.
              </label>
            </div>

            <button type="submit" class="btn btn-ml w-100 py-2" [disabled]="busy()">
              @if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }
              <i class="bi bi-person-plus me-1"></i> Create Account
            </button>
          </form>

          <div class="text-center mt-4 small">
            <span class="text-muted">Already have an account?</span>
            <a routerLink="/login" class="fw-semibold text-decoration-none">Log in</a>
          </div>
        </div>
      </div>
    </div>
  `,
})
export class RegisterComponent {
  private auth = inject(AuthService);
  private toast = inject(ToastService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);

  /** ?role=farmer deep-link from the footer/hero, mirroring the controller default. */
  role = signal<'customer' | 'farmer'>(
    this.route.snapshot.queryParamMap.get('role') === 'farmer' ? 'farmer' : 'customer',
  );
  busy = signal(false);
  terms = false;

  f = {
    name: '', email: '', phone: '', password: '', password_confirmation: '',
    address: '', business_name: '', contact_person: '', business_address: '',
  };

  roleIs(r: string): boolean {
    return this.role() === r;
  }

  async submit(): Promise<void> {
    if (!this.terms) {
      this.toast.error("Please agree to MarketLink's Terms of Service and Privacy Policy.");
      return;
    }
    if (this.f.password !== this.f.password_confirmation) {
      this.toast.error('Passwords do not match.');
      return;
    }
    this.busy.set(true);
    try {
      const fields: Record<string, unknown> = { role: this.role(), ...this.f };
      if (this.roleIs('farmer')) delete fields['address'];
      else {
        delete fields['business_name'];
        delete fields['contact_person'];
        delete fields['business_address'];
      }
      const res = await this.auth.register(fields);
      this.toast.success('Welcome to MarketLink!');
      this.router.navigateByUrl(res?.redirect ?? '/');
    } catch (e) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }
}

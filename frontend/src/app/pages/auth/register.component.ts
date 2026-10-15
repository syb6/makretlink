import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth.service';
import { ToastService } from '../../core/toast.service';

@Component({
  selector: 'app-register',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule, RouterLink],
  template: `
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-7">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h2 class="fw-bold mb-1">Join MarketLink</h2>
            <p class="text-muted small mb-3">Customers pre-order. Farmers sell at the market (admin approval required).</p>

            <div class="btn-group w-100 mb-3" role="group">
              <button type="button" class="btn" [class.btn-success]="role() === 'customer'" [class.btn-outline-success]="role() !== 'customer'" (click)="role.set('customer')">
                <i class="bi bi-bag me-1"></i>I'm a customer
              </button>
              <button type="button" class="btn" [class.btn-success]="role() === 'farmer'" [class.btn-outline-success]="role() !== 'farmer'" (click)="role.set('farmer')">
                <i class="bi bi-basket2 me-1"></i>I'm a farmer
              </button>
            </div>

            <form (ngSubmit)="submit()" autocomplete="off">
              <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Full name</label><input class="form-control" name="name" [(ngModel)]="f.name" required maxlength="120"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone" [(ngModel)]="f.phone" required maxlength="30"></div>
              </div>
              <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" [(ngModel)]="f.email" required></div>

              @if (roleIs('customer')) {
                <div class="mb-3"><label class="form-label">Delivery/pickup address</label><textarea class="form-control" name="address" [(ngModel)]="f.address" required rows="2" maxlength="1000"></textarea></div>
              } @else {
                <div class="row">
                  <div class="col-md-6 mb-3"><label class="form-label">Business name</label><input class="form-control" name="business_name" [(ngModel)]="f.business_name" required maxlength="150"></div>
                  <div class="col-md-6 mb-3"><label class="form-label">Contact person (optional)</label><input class="form-control" name="contact_person" [(ngModel)]="f.contact_person" maxlength="120"></div>
                </div>
                <div class="mb-3"><label class="form-label">Business address</label><textarea class="form-control" name="business_address" [(ngModel)]="f.business_address" rows="2" maxlength="1000"></textarea></div>
              }

              <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="password" [(ngModel)]="f.password" required minlength="8"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Confirm password</label><input class="form-control" type="password" name="password_confirmation" [(ngModel)]="f.password_confirmation" required minlength="8"></div>
              </div>

              <button class="btn btn-success w-100" [disabled]="busy()">@if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }Create account</button>
            </form>

            <p class="text-center small text-muted mt-3 mb-0">Already a member? <a routerLink="/login">Sign in</a></p>
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

  role = signal<'customer' | 'farmer'>('customer');
  busy = signal(false);

  f: any = {
    name: '', email: '', phone: '', password: '', password_confirmation: '',
    address: '', business_name: '', contact_person: '', business_address: '',
  };

  roleIs(r: string): boolean {
    return this.role() === r;
  }

  async submit(): Promise<void> {
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
    } catch (e: any) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }
}

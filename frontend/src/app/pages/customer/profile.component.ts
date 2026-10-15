import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';
import { AuthService } from '../../core/auth.service';
import { ToastService } from '../../core/toast.service';
import { PersonAvatarComponent } from '../../core/person-avatar.component';

@Component({
  selector: 'app-customer-profile',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule, PersonAvatarComponent],
  template: `
    <h2 class="section-title mb-3">My profile</h2>
    <form class="card shadow-sm" (ngSubmit)="save()">
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
          <app-person-avatar [name]="f.name" [src]="avatarPath()" class="avatar-lg rounded-circle" />
          <label class="btn btn-outline-success btn-sm mb-0">
            Change photo<input type="file" accept="image/png,image/jpeg,image/webp" class="d-none" (change)="photo.set($any($event.target).files?.[0] ?? null)">
          </label>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3"><label class="form-label">Name</label><input class="form-control" name="name" [(ngModel)]="f.name" required maxlength="120"></div>
          <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone" [(ngModel)]="f.phone" required maxlength="30"></div>
        </div>
        <div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="address" [(ngModel)]="f.address" required rows="2" maxlength="1000"></textarea></div>
        <div class="row">
          <div class="col-md-6 mb-3"><label class="form-label">New password (optional)</label><input class="form-control" type="password" name="password" [(ngModel)]="f.password" autocomplete="new-password"></div>
          <div class="col-md-6 mb-3"><label class="form-label">Confirm new password</label><input class="form-control" type="password" name="password_confirmation" [(ngModel)]="f.password_confirmation" autocomplete="new-password"></div>
        </div>
        <button class="btn btn-success" [disabled]="busy()">@if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }Save changes</button>
      </div>
    </form>
  `,
})
export class CustomerProfileComponent implements OnInit {
  private api = inject(ApiService);
  private auth = inject(AuthService);
  private toast = inject(ToastService);

  f: any = { name: '', phone: '', address: '', password: '', password_confirmation: '' };
  avatarPath = signal<string | null>(null);
  photo = signal<File | null>(null);
  busy = signal(false);

  ngOnInit(): void {
    this.api.get<any>('/customer/profile').then((d) => {
      this.f.name = d.user.name;
      this.f.phone = d.user.phone;
      this.f.address = d.profile?.address ?? '';
      this.avatarPath.set(d.user.avatar ?? d.profile?.profile_image ?? null);
    });
  }

  async save(): Promise<void> {
    this.busy.set(true);
    try {
      if (this.photo()) {
        await this.api.upload('/customer/profile', {
          name: this.f.name, phone: this.f.phone, address: this.f.address,
        }, { profile_image: this.photo() });
      } else {
        await this.api.form('/customer/profile', this.f);
      }
      this.toast.success('Profile updated.');
      const user = await this.auth.load();
      this.avatarPath.set(user?.avatar ?? user?.customer_profile?.profile_image ?? null);
    } catch (e) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }
}

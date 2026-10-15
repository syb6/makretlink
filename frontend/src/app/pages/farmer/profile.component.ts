import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';
import { AuthService } from '../../core/auth.service';
import { ToastService } from '../../core/toast.service';
import { PersonAvatarComponent } from '../../core/person-avatar.component';

@Component({
  selector: 'app-farmer-profile',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule, PersonAvatarComponent],
  template: `
    <h2 class="section-title mb-3">Stall profile</h2>
    <form class="card shadow-sm" (ngSubmit)="save()">
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
          <app-person-avatar [name]="f.contact_person" [src]="avatarPath()" class="avatar-lg rounded-circle" />
          <label class="btn btn-outline-success btn-sm mb-0">
            Change photo<input type="file" accept="image/png,image/jpeg,image/webp" class="d-none" (change)="photo.set($any($event.target).files?.[0] ?? null)">
          </label>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3"><label class="form-label">Business name</label><input class="form-control" name="business_name" [(ngModel)]="f.business_name" required maxlength="150"></div>
          <div class="col-md-6 mb-3"><label class="form-label">Contact person</label><input class="form-control" name="contact_person" [(ngModel)]="f.contact_person" required maxlength="120"></div>
        </div>
        <div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="address" [(ngModel)]="f.address" required rows="2" maxlength="1000"></textarea></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" [(ngModel)]="f.description" rows="3" maxlength="2000"></textarea></div>
        <button class="btn btn-success" [disabled]="busy()">@if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }Save changes</button>
      </div>
    </form>
  `,
})
export class FarmerProfileComponent implements OnInit {
  private api = inject(ApiService);
  private auth = inject(AuthService);
  private toast = inject(ToastService);

  f: any = { business_name: '', contact_person: '', address: '', description: '' };
  avatarPath = signal<string | null>(null);
  photo = signal<File | null>(null);
  busy = signal(false);
  ngOnInit(): void {
    this.api.get<any>('/farmer/profile').then((d) => {
      Object.assign(this.f, d.farmer);
      this.avatarPath.set(d.avatar ?? d.farmer.profile_image);
    });
  }

  async save(): Promise<void> {
    this.busy.set(true);
    try {
      if (this.photo()) {
        await this.api.upload('/farmer/profile', {
          business_name: this.f.business_name, contact_person: this.f.contact_person,
          address: this.f.address, description: this.f.description ?? '',
        }, { profile_image: this.photo() });
      } else {
        await this.api.form('/farmer/profile', { ...this.f, description: this.f.description ?? '' });
      }
      this.toast.success('Profile updated.');
      const user = await this.auth.load();
      this.avatarPath.set(user?.avatar ?? user?.farmer_profile?.profile_image ?? null);
    } catch (e) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }
}

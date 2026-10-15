import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { statusLabel, statusBadge } from '../../core/utils';
import { PersonAvatarComponent } from '../../core/person-avatar.component';

@Component({
  selector: 'app-admin-users',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule, PersonAvatarComponent],
  template: `
    <h2 class="section-title mb-3">Users</h2>

    <form class="card mb-3" autocomplete="off">
      <div class="card-body row g-2 align-items-end">
        <div class="col-md-4"><input class="form-control form-control-sm" name="q" [(ngModel)]="q" placeholder="Search name or email…" (input)="load()"></div>
        <div class="col-md-2"><select class="form-select form-select-sm" name="role" [(ngModel)]="role" (change)="load()">
          <option value="">Any role</option><option value="admin">Admin</option><option value="farmer">Farmer</option><option value="customer">Customer</option>
        </select></div>
        <div class="col-md-2"><select class="form-select form-select-sm" name="status" [(ngModel)]="status" (change)="load()">
          <option value="">Any status</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="suspended">Suspended</option>
        </select></div>
        <div class="col-md-2"><select class="form-select form-select-sm" name="approval" [(ngModel)]="approval" (change)="load()">
          <option value="">Any approval</option><option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option>
        </select></div>
      </div>
    </form>

    <div class="table-responsive card shadow-sm">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Farmer approval</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
          @for (u of rows(); track u.id) {
            <tr>
              <td><div class="d-flex align-items-center gap-2">
                <app-person-avatar [name]="u.name" [src]="u.avatar" class="avatar-sm rounded-circle" />
                <div><div class="fw-semibold">{{ u.name }}</div><div class="small text-muted">{{ u.email }}</div></div>
              </div></td>
              <td><span class="badge" [class]="'text-bg-' + badge(u.role)">{{ u.role }}</span></td>
              <td>
                <select class="form-select form-select-sm" style="width:auto" [ngModel]="u.status" (ngModelChange)="setStatus(u, $event)">
                  @for (s of ['active', 'inactive', 'suspended']; track s) {
                    <option [value]="s" [selected]="u.status === s">{{ label(s) }}</option>
                  }
                </select>
              </td>
              <td>
                @if (u.farmer_profile) {
                  <span class="badge" [class]="'text-bg-' + badge(u.farmer_profile.approval_status)">{{ label(u.farmer_profile.approval_status) }}</span>
                  @if (u.farmer_profile.approval_status !== 'approved') {
                    <div class="mt-1 d-flex gap-1">
                      <button class="btn btn-sm btn-outline-success" (click)="approve(u)">Approve</button>
                      <button class="btn btn-sm btn-outline-danger" (click)="reject(u)">Reject</button>
                    </div>
                  }
                } @else { <span class="text-muted small">—</span> }
              </td>
              <td class="text-end">
                <span class="text-muted small">ID {{ u.id }}</span>
              </td>
            </tr>
          }
        </tbody>
      </table>
    </div>

    @if (lastPage() > 1) {
      <nav class="mt-3"><ul class="pagination justify-content-center">
        @for (p of pages(); track p) {
          <li class="page-item" [class.active]="p === page()"><button class="page-link" (click)="page.set(p); load()">{{ p }}</button></li>
        }
      </ul></nav>
    }
  `,
})
export class AdminUsersComponent implements OnInit {
  private api = inject(ApiService);
  private route = inject(ActivatedRoute);
  private toast = inject(ToastService);

  rows = signal<any[]>([]);
  q = ''; role = ''; status = ''; approval = '';
  page = signal(1);
  lastPage = signal(1);

  label = statusLabel; badge = statusBadge;

  ngOnInit(): void {
    this.role = this.route.snapshot.queryParamMap.get('role') ?? '';
    this.approval = this.route.snapshot.queryParamMap.get('approval') ?? '';
    void this.load();
  }

  pages(): number[] { return Array.from({ length: this.lastPage() }, (_, i) => i + 1); }

  async load(): Promise<void> {
    const qs = new URLSearchParams({ page: String(this.page()) });
    if (this.q) qs.set('q', this.q);
    if (this.role) qs.set('role', this.role);
    if (this.status) qs.set('status', this.status);
    if (this.approval) qs.set('approval', this.approval);
    const d = await this.api.get<any>('/admin/users?' + qs);
    this.rows.set(d.rows);
    this.page.set(d.page);
    this.lastPage.set(d.last_page);
  }

  async setStatus(u: any, status: string): Promise<void> {
    try {
      const res = await this.api.form<{ flash: Record<string, string> }>('/admin/users/' + u.id + '/status', { status });
      if (res.flash?.['error']) this.toast.error(res.flash['error']);
      else this.toast.success('User status updated.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
      void this.load();
    }
  }

  async approve(u: any): Promise<void> {
    try {
      await this.api.form('/admin/farmers/' + u.id + '/approve', {});
      this.toast.success(u.name + ' approved as a farmer.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  async reject(u: any): Promise<void> {
    try {
      await this.api.form('/admin/farmers/' + u.id + '/reject', {});
      this.toast.success('Farmer registration rejected.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

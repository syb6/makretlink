import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { dayName, formatTime, imageError, img, statusLabel } from '../../core/utils';

@Component({
  selector: 'app-admin-markets',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule],
  template: `
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="section-title mb-0">Markets</h2>
      <button class="btn btn-ml btn-sm" (click)="startNew()"><i class="bi bi-plus-lg me-1"></i>New market</button>
    </div>

    @if (editing(); as m) {
      <div class="modal d-block" style="background: rgba(0,0,0,.4)" (click)="editing.set(null)">
        <div class="modal-dialog modal-lg" (click)="$event.stopPropagation()">
          <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ m.id ? 'Edit market' : 'New market' }}</h5></div>
            <div class="modal-body">
              <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Name</label><input class="form-control" name="name" [(ngModel)]="form.name" required maxlength="150"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Status</label>
                  <select class="form-select" name="status" [(ngModel)]="form.status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                </div>
                <div class="col-12 mb-3"><label class="form-label">Address</label><textarea class="form-control" name="address" [(ngModel)]="form.address" required rows="2" maxlength="1000"></textarea></div>
                <div class="col-md-6 mb-3"><label class="form-label">Latitude</label><input class="form-control" type="number" step="any" name="latitude" [(ngModel)]="form.latitude"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Longitude</label><input class="form-control" type="number" step="any" name="longitude" [(ngModel)]="form.longitude"></div>
                <div class="col-12 mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" [(ngModel)]="form.description" rows="2"></textarea></div>
                <div class="col-12 mb-3"><label class="form-label">Photo (min 200×120)</label><input class="form-control" type="file" accept="image/png,image/jpeg,image/webp" (change)="image.set($any($event.target).files?.[0] ?? null)"></div>
                <div class="col-12">
                  <h6 class="fw-bold mb-2">Opening hours per day</h6>
                  <table class="table table-sm">
                    <tbody>
                      @for (d of [0,1,2,3,4,5,6]; track d) {
                        <tr>
                          <td style="width:110px">{{ dayName(d) }}</td>
                          <td><div class="d-flex gap-1 align-items-center">
                            <input class="form-control form-control-sm" type="time" style="width:110px" [ngModel]="form.days[d]?.open" (ngModelChange)="form.days[d] = form.days[d] ?? {}; form.days[d].open = $event" [name]="'open' + d">
                            <span>–</span>
                            <input class="form-control form-control-sm" type="time" style="width:110px" [ngModel]="form.days[d]?.close" (ngModelChange)="form.days[d] = form.days[d] ?? {}; form.days[d].close = $event" [name]="'close' + d">
                            @if (!form.days[d]?.open || !form.days[d]?.close) { <span class="text-muted small">closed</span> }
                          </div></td>
                        </tr>
                      }
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-outline-secondary" (click)="editing.set(null)">Cancel</button>
              <button class="btn btn-ml" (click)="save()" [disabled]="busy()">@if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }Save</button>
            </div>
          </div>
        </div>
    </div>
    }

    <div class="table-responsive card shadow-sm">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Market</th><th>Schedules</th><th>Stalls</th><th>Status</th><th class="text-end"></th></tr></thead>
        <tbody>
          @for (m of rows(); track m.id) {
            <tr>
              <td><div class="d-flex align-items-center gap-2">
                <img [src]="img('market', m.image)" (error)="onImageError($event, 'market')" style="width:40px;height:40px;object-fit:cover" class="rounded" alt="">
                <div><div class="fw-semibold">{{ m.name }}</div><div class="small text-muted">{{ m.address }}</div></div>
              </div></td>
              <td class="small">
                @for (s of m.schedules; track s.id) {
                  <span class="badge bg-light text-dark me-1">{{ dayName(s.day_of_week) }}@if (!s.is_closed) { {{ short(s.opening_time) }}–{{ short(s.closing_time) }} }</span>
                }
              </td>
              <td>{{ m.farmer_markets_count }}</td>
              <td><span class="badge" [class]="m.status === 'active' ? 'text-bg-success' : 'text-bg-secondary'">{{ label(m.status) }}</span></td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-ml me-1" (click)="startEdit(m)">Edit</button>
                <button class="btn btn-sm btn-outline-danger" (click)="remove(m)"><i class="bi bi-trash"></i></button>
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
export class AdminMarketsComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  rows = signal<any[]>([]);
  page = signal(1);
  lastPage = signal(1);
  editing = signal<any>(null);
  form: any = {};
  image = signal<File | null>(null);
  busy = signal(false);

  dayName = dayName; img = img; label = statusLabel; onImageError = imageError;

  ngOnInit(): void {
    void this.load();
  }

  pages(): number[] { return Array.from({ length: this.lastPage() }, (_, i) => i + 1); }

  short(t: string | null): string { return formatTime(t); }

  async load(): Promise<void> {
    const d = await this.api.get<any>('/admin/markets?page=' + this.page());
    this.rows.set(d.rows);
    this.page.set(d.page);
    this.lastPage.set(d.last_page);
  }

  startNew(): void {
    this.form = { name: '', address: '', latitude: '', longitude: '', description: '', status: 'active', days: {} };
    this.image.set(null);
    this.editing.set({});
  }

  startEdit(m: any): void {
    const days: any = {};
    for (const s of m.schedules ?? []) days[s.day_of_week] = { open: formatTime(s.opening_time), close: formatTime(s.closing_time) };
    this.form = { name: m.name, address: m.address, latitude: m.latitude ?? '', longitude: m.longitude ?? '', description: m.description ?? '', status: m.status, days };
    this.image.set(null);
    this.editing.set(m);
  }

  async save(): Promise<void> {
    this.busy.set(true);
    try {
      const fields: Record<string, unknown> = { ...this.form };
      delete fields['days'];
      const days: Record<string, { open?: string; close?: string }> = this.form.days ?? {};
      for (const [day, schedule] of Object.entries(days)) {
        fields[`days[${day}][open]`] = schedule.open ?? '';
        fields[`days[${day}][close]`] = schedule.close ?? '';
      }
      const files: Record<string, File | null> = { image: this.image() };
      const id = this.editing().id;
      if (id) {
        await this.api.upload('/admin/markets/' + id, fields, files);
        this.toast.success('Market updated.');
      } else {
        await this.api.upload('/admin/markets', fields, files);
        this.toast.success('Market created.');
      }
      this.editing.set(null);
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }

  async remove(m: any): Promise<void> {
    try {
      const res = await this.api.del<{ flash: Record<string, string> }>('/admin/markets/' + m.id);
      if (res.flash?.['error']) this.toast.error(res.flash['error']);
      else this.toast.success('Market removed.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

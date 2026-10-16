import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { money, date, statusBadge, statusLabel } from '../../core/utils';

@Component({
  selector: 'app-farmer-slots',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule],
  template: `
    <h2 class="section-title mb-3">Pickup slots</h2>

    <div class="card shadow-sm mb-4">
      <div class="card-body row g-2 align-items-end">
        <div class="col-md-3"><select class="form-select form-select-sm" name="newStall" [(ngModel)]="n.farmer_market_id">
          @for (s of stalls(); track s.id) { <option [value]="s.id">{{ s.market?.name }}</option> }
        </select></div>
        <div class="col-md-3"><label class="form-label small mb-1">Date</label><input class="form-control form-control-sm" type="date" name="date" [(ngModel)]="n.pickup_date"></div>
        <div class="col-md-3"><label class="form-label small mb-1">Window</label><div class="d-flex gap-1">
          <input class="form-control form-control-sm" type="time" name="start" [(ngModel)]="n.start_time">
          <input class="form-control form-control-sm" type="time" name="end" [(ngModel)]="n.end_time">
        </div></div>
        <div class="col-md-3"><label class="form-label small mb-1">Cutoff (before)</label><input class="form-control form-control-sm" type="datetime-local" name="cutoff" [(ngModel)]="n.cutoff_at"></div>
        <div class="col-md-2"><label class="form-label small mb-1">Max orders</label><input class="form-control form-control-sm" type="number" min="1" name="max" [(ngModel)]="n.max_orders"></div>
        <div class="col-md-2 d-grid"><button class="btn btn-sm btn-ml" (click)="create()">Create</button></div>
      </div>
    </div>

    @for (dateStr of dates(); track dateStr) {
      <h6 class="fw-bold mt-4">{{ date(dateStr) }}</h6>
      <div class="table-responsive card shadow-sm">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Stall</th><th>Window</th><th>Cutoff</th><th>Orders</th><th>Status</th><th></th></tr></thead>
          <tbody>
            @for (s of slotsFor(dateStr); track s.id) {
              <tr>
                <td class="small">{{ s.farmer_market?.market?.name }}</td>
                <td>{{ s.start_time }} – {{ s.end_time }}</td>
                <td class="small">{{ date(s.cutoff_at, true) }}</td>
                <td>{{ s.order_count }}/@if (s.max_orders) { {{ s.max_orders }} } @else { ∞ }</td>
                <td><span class="badge" [class]="'text-bg-' + badge(s.status)">{{ label(s.status) }}</span></td>
                <td class="text-end">
                  <button class="btn btn-sm me-1" [class.btn-outline-secondary]="s.status === 'active'" [class.btn-outline-ml]="s.status !== 'active'" (click)="toggle(s)">
                    {{ s.status === 'active' ? 'Deactivate' : 'Activate' }}
                  </button>
                  <button class="btn btn-outline-danger btn-sm" (click)="remove(s)"><i class="bi bi-trash"></i></button>
                </td>
              </tr>
            }
          </tbody>
        </table>
      </div>
    } @empty { <p class="text-muted">No slots yet.</p> }
  `,
})
export class FarmerSlotsComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  grouped = signal<Record<string, any[]>>({});
  stalls = signal<any[]>([]);
  n: any = { farmer_market_id: '', pickup_date: '', start_time: '08:00', end_time: '10:00', cutoff_at: '', max_orders: '' };

  money = money; date = date; badge = statusBadge; label = statusLabel;

  ngOnInit(): void {
    void this.load();
  }

  dates(): string[] { return Object.keys(this.grouped()).sort(); }
  slotsFor(d: string): any[] { return this.grouped()[d] ?? []; }

  async load(): Promise<void> {
    const d = await this.api.get<any>('/farmer/slots');
    this.grouped.set(d.grouped);
    this.stalls.set(d.farmer_markets);
    if (!this.n.farmer_market_id && d.farmer_markets.length) this.n.farmer_market_id = d.farmer_markets[0].id;
  }

  async create(): Promise<void> {
    if (!this.n.farmer_market_id || !this.n.pickup_date || !this.n.cutoff_at) {
      this.toast.error('Stall, date and cutoff are required.');
      return;
    }
    try {
      const res = await this.api.form<{ flash: Record<string, string>; redirect?: string }>('/farmer/slots', this.n);
      if (res.flash?.['error']) this.toast.error(String(res.flash['error']));
      else this.toast.success('Pickup slot created.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  async toggle(s: any): Promise<void> {
    try {
      await this.api.form('/farmer/slots/' + s.id, { status: s.status === 'active' ? 'inactive' : 'active' });
      this.toast.success('Slot updated.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  async remove(s: any): Promise<void> {
    try {
      const res = await this.api.del<{ flash: Record<string, string> }>('/farmer/slots/' + s.id);
      if (res.flash?.['error']) this.toast.error(String(res.flash['error']));
      else this.toast.success('Slot removed.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

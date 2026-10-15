import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { money, date, statusLabel, statusBadge } from '../../core/utils';

@Component({
  selector: 'app-farmer-orders',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, FormsModule],
  template: `
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="section-title mb-0">Orders</h2>
      <select class="form-select form-select-sm" style="width:auto" name="statusFilter" [ngModel]="filter()" (ngModelChange)="filter.set($event); load()">
        <option value="">All statuses</option>
        @for (s of statuses(); track s) { <option [value]="s">{{ label(s) }}</option> }
      </select>    </div>
    

    <div class="table-responsive card shadow-sm">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>#</th><th>Customer</th><th>Pickup</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @for (o of rows(); track o.id) {
            <tr>
              <td class="fw-semibold">{{ o.order_number }}</td>
              <td><div>{{ o.customer?.user?.name }}</div><div class="small text-muted">{{ o.customer?.user?.phone }}</div></td>
              <td class="small">{{ date(o.pickup_date) }} {{ o.pickup_start_time }}</td>
              <td>{{ o.items_count }}</td>
              <td>{{ money(o.total_amount) }}</td>
              <td><span class="badge" [class]="'text-bg-' + badge(o.status)">{{ label(o.status) }}</span></td>
              <td class="text-end"><a [routerLink]="['/farmer/orders', o.id]" class="btn btn-sm btn-outline-success">Manage</a></td>
            </tr>
          } @empty { <tr><td colspan="7" class="text-muted">No orders match.</td></tr> }
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
export class FarmerOrdersComponent implements OnInit {
  private api = inject(ApiService);

  rows = signal<any[]>([]);
  statuses = signal<string[]>([]);
  filter = signal('');
  page = signal(1);
  lastPage = signal(1);

  money = money; date = date; label = statusLabel; badge = statusBadge;

  ngOnInit(): void {
    void this.load();
  }

  pages(): number[] { return Array.from({ length: this.lastPage() }, (_, i) => i + 1); }

  async load(): Promise<void> {
    const qs = new URLSearchParams({ page: String(this.page()) });
    if (this.filter()) qs.set('status', this.filter());
    const d = await this.api.get<any>('/farmer/orders?' + qs);
    this.rows.set(d.rows);
    this.statuses.set(d.statuses ?? []);
    this.page.set(d.page);
    this.lastPage.set(d.last_page);
  }
}

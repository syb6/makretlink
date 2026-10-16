import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { money, date, statusLabel, statusBadge } from '../../core/utils';

@Component({
  selector: 'app-customer-orders',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink],
  template: `
    <h2 class="section-title mb-3">My orders</h2>

    <div class="table-responsive card shadow-sm">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Order</th><th>Farmer</th><th>Pickup</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @for (o of rows(); track o.id) {
            <tr>
              <td><a [routerLink]="['/customer/orders', o.id]" class="text-decoration-none fw-semibold">{{ o.order_number }}</a></td>
              <td>{{ o.farmer_name }}</td>
              <td class="small">{{ date(o.pickup_date) }} {{ o.pickup_start_time }}</td>
              <td>{{ o.items?.length }}</td>
              <td>{{ money(o.total_amount) }}</td>
              <td><span class="badge" [class]="'text-bg-' + badge(o.status)">{{ label(o.status) }}</span></td>
              <td class="text-end">
                <a [routerLink]="['/customer/orders', o.id]" class="btn btn-sm btn-outline-ml">Details</a>
              </td>
            </tr>
          } @empty {
            <tr><td colspan="7" class="text-muted">No orders yet.</td></tr>
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
export class CustomerOrdersComponent implements OnInit {
  private api = inject(ApiService);

  rows = signal<any[]>([]);
  page = signal(1);
  lastPage = signal(1);

  money = money; date = date; label = statusLabel; badge = statusBadge;

  ngOnInit(): void {
    void this.load();
  }

  pages(): number[] { return Array.from({ length: this.lastPage() }, (_, i) => i + 1); }

  async load(): Promise<void> {
    const d = await this.api.get<any>('/customer/orders?page=' + this.page());
    this.rows.set(d.rows);
    this.page.set(d.page);
    this.lastPage.set(d.last_page);
  }
}

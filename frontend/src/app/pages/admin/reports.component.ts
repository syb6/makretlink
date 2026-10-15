import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { ApiService } from '../../core/api.service';
import { money, statusLabel } from '../../core/utils';

@Component({
  selector: 'app-admin-reports',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [],
  template: `
    <h2 class="section-title mb-3">Reports</h2>

    <div class="row g-3 mb-4">
      <div class="col-md-6"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Total orders</div><div class="stat-value">{{ d()?.total_orders ?? 0 }}</div></div></div></div>
      <div class="col-md-6"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Revenue (committed)</div><div class="stat-value">{{ money(d()?.total_revenue) }}</div></div></div></div>
    </div>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card shadow-sm h-100"><div class="card-header bg-transparent fw-bold">Orders by status</div>
          <table class="table table-sm mb-0 align-middle">
            <tbody>
              @for (s of d()?.orders_by_status ?? []; track s.status) {
                <tr><td>{{ label(s.status) }}</td><td class="text-end">{{ s.count }}</td><td class="text-end small text-muted">{{ money(s.total) }}</td></tr>
              }
            </tbody>
          </table>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card shadow-sm h-100"><div class="card-header bg-transparent fw-bold">Revenue by market</div>
          <table class="table table-sm mb-0 align-middle">
            <tbody>
              @for (m of d()?.revenue_by_market ?? []; track m.name) {
                <tr><td>{{ m.name }}</td><td class="text-end">{{ m.orders }}</td><td class="text-end fw-semibold">{{ money(m.revenue) }}</td></tr>
              } @empty { <tr><td class="text-muted">No data.</td></tr> }
            </tbody>
          </table>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card shadow-sm h-100"><div class="card-header bg-transparent fw-bold">Most active farmers</div>
          <table class="table table-sm mb-0 align-middle">
            <tbody>
              @for (f of d()?.most_active_farmers ?? []; track f.business_name) {
                <tr><td>{{ f.business_name }}</td><td class="text-end">{{ f.orders }} orders</td><td class="text-end small text-muted">{{ money(f.revenue) }}</td></tr>
              } @empty { <tr><td class="text-muted">No data.</td></tr> }
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `,
})
export class AdminReportsComponent implements OnInit {
  private api = inject(ApiService);

  d = signal<any>(null);
  money = money; label = statusLabel;

  ngOnInit(): void {
    this.api.get<any>('/admin/reports').then((x) => this.d.set(x));
  }
}

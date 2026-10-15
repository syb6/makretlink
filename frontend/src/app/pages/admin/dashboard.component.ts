import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { money, date, statusLabel } from '../../core/utils';

@Component({
  selector: 'app-admin-dashboard',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink],
  template: `
    @if (loading()) {
      <div class="text-center py-5" role="status"><span class="spinner-border text-success"></span><span class="visually-hidden">Loading admin dashboard</span></div>
    } @else if (!d()) {
      <div class="alert alert-danger" role="alert">The admin dashboard could not be loaded. Use “Reload this page” above to try again.</div>
    } @else {
      <div class="mb-4">
        <h2 class="h3 mb-1">System Overview</h2>
        <p class="text-muted mb-0">Monitor platform activity, pending approvals, and top performers.</p>
      </div>

      <div class="row g-4 mb-4">
        <div class="col-xl-3 col-sm-6">
          <div class="stat-card"><div class="stat-icon"><i class="bi bi-people"></i></div><div><div class="stat-number">{{ d().total_customers ?? 0 }}</div><div class="stat-label">Total Customers ({{ d().active_customers ?? 0 }} active)</div></div></div>
        </div>
        <div class="col-xl-3 col-sm-6">
          <div class="stat-card"><div class="stat-icon"><i class="bi bi-shop-window"></i></div><div><div class="stat-number">{{ d().total_farmers ?? 0 }}</div><div class="stat-label">Registered Farmers ({{ d().pending_farmers ?? 0 }} pending)</div></div></div>
        </div>
        <div class="col-xl-3 col-sm-6">
          <div class="stat-card"><div class="stat-icon"><i class="bi bi-geo-alt"></i></div><div><div class="stat-number">{{ d().total_orders ?? 0 }}</div><div class="stat-label">Orders ({{ d().pending_orders ?? 0 }} to accept)</div></div></div>
        </div>
        <div class="col-xl-3 col-sm-6">
          <div class="stat-card"><div class="stat-icon"><i class="bi bi-currency-dollar"></i></div><div><div class="stat-number">{{ money(d().revenue) }}</div><div class="stat-label">Platform Volume</div></div></div>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="dashboard-card h-100">
            <div class="dashboard-card-header">
              <h2 class="h5 mb-0">Recent Platform Orders</h2>
              <a routerLink="/admin/reports" class="btn btn-sm btn-outline-ml">Reports</a>
            </div>
            <div class="dashboard-card-body">
              @for (o of d().recent_orders ?? []; track o.id) {
                <div class="order-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <div>
                    <strong>{{ o.order_number }}</strong>
                    <div class="small text-muted">{{ o.customer?.user?.name ?? 'Guest' }} &rarr; from {{ o.market_name }}</div>
                    <div class="small text-muted">Pickup {{ date(o.pickup_date) }}</div>
                  </div>
                  <div class="d-flex align-items-center gap-3">
                    <strong>{{ money(o.total_amount) }}</strong>
                    <span [class]="'status-pill status-' + o.status">{{ label(o.status) }}</span>
                  </div>
                </div>
              } @empty {
                <div class="empty"><i class="bi bi-receipt"></i><span>No orders yet.</span></div>
              }
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="dashboard-card h-100">
            <div class="dashboard-card-header">
              <h2 class="h5 mb-0"><i class="bi bi-shield-lock text-success me-1"></i>Pending Approvals</h2>
            </div>
            <div class="dashboard-card-body">
              <div class="admin-pending-summary p-3 mb-3 rounded">
                <strong class="d-block small">{{ d().pending_farmers ?? 0 }} farmer{{ d().pending_farmers === 1 ? '' : 's' }} waiting</strong>
                <span class="small text-muted">Review new stall applications before they can list products.</span>
              </div>
              <a routerLink="/admin/users" [queryParams]="{ role: 'farmer', approval: 'pending' }" class="btn btn-ml w-100">Review applications</a>
              <hr class="my-3">
              <div class="d-grid gap-2">
                <a routerLink="/admin/markets" class="btn btn-sm btn-outline-ml"><i class="bi bi-shop me-1"></i>Manage markets</a>
                <a routerLink="/admin/categories" class="btn btn-sm btn-outline-ml"><i class="bi bi-tags me-1"></i>Manage categories</a>
                <a routerLink="/admin/announcements" class="btn btn-sm btn-outline-ml"><i class="bi bi-megaphone me-1"></i>Broadcast announcement</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    }
  `,
  styles: `
    .admin-pending-summary { background: var(--accent-warn-soft); border-left: 3px solid var(--accent-warn); }
  `,
})
export class AdminDashboardComponent implements OnInit {
  private readonly api = inject(ApiService);
  readonly d = signal<any>(null);
  readonly loading = signal(true);
  readonly money = money;
  readonly date = date;
  readonly label = statusLabel;

  ngOnInit(): void {
    void this.load();
  }

  private async load(): Promise<void> {
    try {
      this.d.set(await this.api.get<any>('/admin/dashboard'));
    } catch {
      this.d.set(null);
    } finally {
      this.loading.set(false);
    }
  }
}

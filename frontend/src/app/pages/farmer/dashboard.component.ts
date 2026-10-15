import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { money, date, formatTime, statusLabel } from '../../core/utils';

@Component({
  selector: 'app-farmer-dashboard',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink],
  template: `
    <section class="py-4 py-md-5">
      <div class="container">
        @if (loading()) {
          <div class="text-center py-5" role="status"><span class="spinner-border text-success"></span><span class="visually-hidden">Loading your dashboard</span></div>
        } @else if (!d()) {
          <div class="alert alert-danger" role="alert">Your dashboard could not be loaded. Use “Reload this page” above to try again.</div>
        } @else {
          <div class="mb-4 d-md-flex justify-content-between align-items-center">
            <div>
              <span class="badge-sub">FARMER MANAGEMENT PORTAL</span>
              <h1 class="h2 mb-1">{{ d().farmers?.business_name }}</h1>
              <p class="text-muted mb-0">Manage your products, track orders, and view customer reviews.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
              <span class="align-self-center" [class]="'status-pill status-' + d().farmers?.approval_status">{{ label(d().farmers?.approval_status) }}</span>
              <a routerLink="/farmer/products" class="btn btn-ml"><i class="bi bi-plus-circle me-1"></i>Add New Product</a>
            </div>
          </div>

          @if (d().farmers?.approval_status !== 'approved') {
            <div class="alert alert-warning">
              <i class="bi bi-exclamation-triangle me-2"></i>Your stall is not approved yet — an administrator must approve it before your products appear publicly.
            </div>
          }

          <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
              <div class="stat-card"><div class="stat-icon"><i class="bi bi-currency-dollar"></i></div><div><div class="stat-label">Total Revenue</div><div class="stat-number">{{ money(d().revenue) }}</div></div></div>
            </div>
            <div class="col-md-3 col-sm-6">
              <div class="stat-card"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-label">Pending Orders</div><div class="stat-number">{{ d().pending_orders ?? 0 }}</div></div></div>
            </div>
            <div class="col-md-3 col-sm-6">
              <div class="stat-card"><div class="stat-icon"><i class="bi bi-box-seam"></i></div><div><div class="stat-label">Active Products</div><div class="stat-number">{{ d().product_count ?? 0 }}</div></div></div>
            </div>
            <div class="col-md-3 col-sm-6">
              <div class="stat-card"><div class="stat-icon"><i class="bi bi-bag-check"></i></div><div><div class="stat-label">Total Orders</div><div class="stat-number">{{ d().total_orders ?? 0 }}</div></div></div>
            </div>
          </div>

          <div class="row g-4">
            <div class="col-lg-8">
              <div class="dashboard-card h-100">
                <div class="dashboard-card-header">
                  <h2 class="h5 mb-0">Recent Orders</h2>
                  <a routerLink="/farmer/orders" class="btn btn-sm btn-outline-ml">Manage Orders</a>
                </div>
                <div class="dashboard-card-body">
                  @for (o of d().recent_orders ?? []; track o.id) {
                    <div class="border rounded p-3 mb-3">
                      <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <a [routerLink]="['/farmer/orders', o.id]" class="fw-bold text-decoration-none">{{ o.order_number }}</a>
                        <span [class]="'status-pill status-' + o.status">{{ label(o.status) }}</span>
                      </div>
                      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                          <div class="avatar-circle farmer-customer-avatar">{{ initials(o.customer?.user?.name) }}</div>
                          <div>
                            <strong class="d-block small">{{ o.customer?.user?.name ?? 'Guest' }}</strong>
                            <small class="text-muted">Pickup: {{ date(o.pickup_date) }} &middot; {{ formatTime(o.pickup_start_time) }}</small>
                          </div>
                        </div>
                        <strong>{{ money(o.total_amount) }}</strong>
                      </div>
                    </div>
                  } @empty {
                    <div class="empty">
                      <i class="bi bi-check-circle text-success"></i>
                      <strong>No orders yet.</strong>
                      <p class="small mb-0">Publish weekly stock so customers can pre-order from you.</p>
                    </div>
                  }
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="dashboard-card mb-4">
                <div class="dashboard-card-header"><h2 class="h5 mb-0"><i class="bi bi-trophy text-success me-1"></i>Best Sellers</h2></div>
                <div class="dashboard-card-body">
                  @for (b of d().best_sellers ?? []; track b.product_name) {
                    <div class="d-flex align-items-center gap-3 p-2 mb-2 border rounded">
                      <div class="stat-icon farmer-best-seller-icon"><i class="bi bi-basket"></i></div>
                      <div class="flex-grow-1">
                        <strong class="d-block small">{{ b.product_name }}</strong>
                        <small class="text-muted">{{ b.total_qty }} sold · {{ money(b.total_sales) }}</small>
                      </div>
                    </div>
                  } @empty {
                    <div class="empty"><i class="bi bi-basket2"></i><span class="small">No sales data yet.</span></div>
                  }
                </div>
              </div>

              <div class="dashboard-card">
                <div class="dashboard-card-header"><h2 class="h5 mb-0">Manage Your Stall</h2></div>
                <div class="dashboard-card-body d-flex flex-column gap-2">
                  <a routerLink="/farmer/stalls" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-shop text-success me-2"></i>My Stalls &amp; Markets</a>
                  <a routerLink="/farmer/products" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-box-seam text-success me-2"></i>Products</a>
                  <a routerLink="/farmer/stock" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-boxes text-success me-2"></i>Weekly Stock &amp; Templates</a>
                  <a routerLink="/farmer/slots" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-calendar-week text-success me-2"></i>Pickup Slots</a>
                  <a routerLink="/farmer/reviews" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-chat-square-text text-success me-2"></i>Reviews</a>
                  <a routerLink="/farmer/profile" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-person-gear text-success me-2"></i>Stall Profile</a>
                </div>
              </div>
            </div>
          </div>
        }
      </div>
    </section>
  `,
  styles: `
    .farmer-customer-avatar { width: 36px; height: 36px; font-size: .8rem; flex: 0 0 auto; }
    .farmer-best-seller-icon { width: 36px; height: 36px; font-size: 1rem; flex: 0 0 auto; }
  `,
})
export class FarmerDashboardComponent implements OnInit {
  private readonly api = inject(ApiService);
  readonly d = signal<any>(null);
  readonly loading = signal(true);
  readonly money = money;
  readonly date = date;
  readonly formatTime = formatTime;
  readonly label = statusLabel;

  initials(name?: string): string {
    return (name ?? 'G')
      .trim()
      .split(/\s+/)
      .slice(0, 2)
      .map((part) => part.charAt(0))
      .join('')
      .toUpperCase();
  }

  ngOnInit(): void {
    void this.load();
  }

  private async load(): Promise<void> {
    try {
      this.d.set(await this.api.get<any>('/farmer/dashboard'));
    } catch {
      this.d.set(null);
    } finally {
      this.loading.set(false);
    }
  }
}

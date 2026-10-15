import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { AuthService } from '../../core/auth.service';
import { money, date, formatTime, statusLabel } from '../../core/utils';

@Component({
  selector: 'app-customer-dashboard',
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
          <div class="mb-4">
            <span class="badge-sub">CUSTOMER MARKET PORTAL</span>
            <h1 class="h2 mb-1">Welcome back, {{ firstName() }}!</h1>
            <p class="text-muted mb-0">Here's what's happening with your pre-orders.</p>
          </div>

          <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
              <div class="stat-card"><div class="stat-icon"><i class="bi bi-bag"></i></div><div><div class="stat-label">Total Orders</div><div class="stat-number">{{ d().total_orders ?? 0 }}</div></div></div>
            </div>
            <div class="col-md-3 col-sm-6">
              <div class="stat-card"><div class="stat-icon"><i class="bi bi-clock-history"></i></div><div><div class="stat-label">Pending / Ready</div><div class="stat-number">{{ d().active_orders ?? 0 }}</div></div></div>
            </div>
            <div class="col-md-3 col-sm-6">
              <div class="stat-card"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div><div class="stat-label">Completed</div><div class="stat-number">{{ d().completed_orders ?? 0 }}</div></div></div>
            </div>
            <div class="col-md-3 col-sm-6">
              <div class="stat-card"><div class="stat-icon"><i class="bi bi-heart"></i></div><div><div class="stat-label">Saved Favorites</div><div class="stat-number">{{ d().favorite_count ?? 0 }}</div></div></div>
            </div>
          </div>

          <div class="row g-4 mb-4">
            <div class="col-lg-8">
              <div class="dashboard-card h-100">
                <div class="dashboard-card-header">
                  <h2 class="h5 mb-0">Recent Orders</h2>
                  <a routerLink="/customer/orders" class="btn btn-sm btn-outline-ml">View All</a>
                </div>
                <div class="dashboard-card-body">
                  @for (o of d().recent_orders ?? []; track o.id) {
                    <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-3 flex-wrap gap-2">
                      <div>
                        <a [routerLink]="['/customer/orders', o.id]" class="fw-bold text-decoration-none">{{ o.order_number }}</a>
                        <div class="small text-muted">{{ o.farmer_name }} &middot; {{ o.market_name }} &middot; Pickup {{ date(o.pickup_date) }}</div>
                      </div>
                      <div class="d-flex align-items-center gap-3 flex-wrap">
                        <strong>{{ money(o.total_amount) }}</strong>
                        <span [class]="'status-pill status-' + o.status">{{ label(o.status) }}</span>
                        <a [routerLink]="['/customer/orders', o.id]" class="small text-decoration-none">Details <i class="bi bi-arrow-right"></i></a>
                      </div>
                    </div>
                  } @empty {
                    <div class="empty">
                      <i class="bi bi-bag-x"></i>
                      <strong>No orders yet.</strong>
                      <p class="small mb-2">Reserve your first basket of fresh produce.</p>
                      <a routerLink="/products" class="btn btn-sm btn-ml">Shop Produce</a>
                    </div>
                  }
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="dashboard-card h-100">
                <div class="dashboard-card-header"><h2 class="h5 mb-0">Quick Actions</h2></div>
                <div class="dashboard-card-body d-flex flex-column gap-2">
                  <a routerLink="/markets" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-shop text-success me-2"></i>Find a Market</a>
                  <a routerLink="/products" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-bag text-success me-2"></i>Shop Produce</a>
                  <a routerLink="/customer/cart" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-basket text-success me-2"></i>Pre-Order Basket</a>
                  <a routerLink="/customer/favorites" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-heart text-danger me-2"></i>My Favorites</a>
                  <a routerLink="/customer/profile" class="btn btn-sm btn-marketlink-outline justify-content-start"><i class="bi bi-person-gear text-success me-2"></i>Profile Settings</a>
                  <hr class="my-1">
                  <div class="text-center py-3 customer-spend">
                    <div class="small text-muted mb-1">Lifetime spend (paid at pickup)</div>
                    <div class="fs-4 fw-bold">{{ money(d().spent) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        }
      </div>
    </section>
  `,
  styles: `
    .customer-spend { background: var(--primary-light); border-radius: 8px; color: var(--primary-contrast); }
  `,
})
export class CustomerDashboardComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly auth = inject(AuthService);
  readonly d = signal<any>(null);
  readonly loading = signal(true);
  readonly money = money;
  readonly date = date;
  readonly formatTime = formatTime;
  readonly label = statusLabel;

  firstName(): string {
    return this.auth.user()?.name.trim().split(/\s+/)[0] ?? 'Customer';
  }

  ngOnInit(): void {
    void this.load();
  }

  private async load(): Promise<void> {
    try {
      this.d.set(await this.api.get<any>('/customer/dashboard'));
    } catch {
      this.d.set(null);
    } finally {
      this.loading.set(false);
    }
  }
}

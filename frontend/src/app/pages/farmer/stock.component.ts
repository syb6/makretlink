import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { imageError, money, img, statusBadge, statusLabel } from '../../core/utils';

@Component({
  selector: 'app-farmer-stock',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule],
  template: `
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="section-title mb-0">Weekly stock</h2>
      <div class="btn-group">
        <button class="btn btn-outline-success btn-sm" (click)="week.set(prevWeek); load()">‹</button>
        <span class="btn btn-light btn-sm" style="pointer-events:none">{{ week() }}</span>
        <button class="btn btn-outline-success btn-sm" (click)="week.set(nextWeek); load()">›</button>
      </div>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-body row g-2 align-items-end">
        <div class="col-md-5"><label class="form-label small">Add stock</label>
          <select class="form-select form-select-sm" name="stock_product" [(ngModel)]="sp.product_id">
            @for (p of products(); track p.id) { <option [value]="p.id">{{ p.name }}</option> }
          </select>
        </div>
        <div class="col-md-3"><select class="form-select form-select-sm" name="stock_stall" [(ngModel)]="sp.farmer_market_id">
          @for (s of stalls(); track s.id) { <option [value]="s.id">{{ s.market?.name }}</option> }
        </select></div>
        <div class="col-md-2"><input class="form-control form-control-sm" type="number" min="0" step="0.5" name="stock_qty" [(ngModel)]="sp.quantity" placeholder="Qty"></div>
        <div class="col-md-2 d-grid"><button class="btn btn-sm btn-success" (click)="saveStock()">Save</button></div>
      </div>
    </div>

    <div class="table-responsive card shadow-sm">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Product</th><th>Stall</th><th>Total</th><th>Available</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @for (s of rows(); track s.id) {
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <img [src]="img('product', s.product?.image)" (error)="onImageError($event, 'product')" style="width:38px;height:38px;object-fit:cover" class="rounded" alt="">
                  <div>
                    <div class="fw-semibold">{{ s.product?.name }}</div>
                    <div class="small text-muted">{{ money(s.product?.price) }} / {{ s.product?.unit }}</div>
                  </div>
                </div>
              </td>
              <td class="small">{{ s.farmerMarket?.market?.name }}</td>
              <td>
                <input class="form-control form-control-sm" style="width:90px" type="number" min="0" step="0.5" [value]="s.quantity" (change)="updateRow(s, +$any($event.target).value, null)">
              </td>
              <td class="fw-semibold">{{ s.available }}</td>
              <td><span class="badge" [class]="'text-bg-' + badge(s.status)">{{ label(s.status) }}</span></td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-success me-1" (click)="updateRow(s, s.quantity, 'available')">Open</button>
                <button class="btn btn-sm btn-outline-danger" (click)="updateRow(s, s.quantity, 'unavailable')">Close</button>
              </td>
            </tr>
          } @empty { <tr><td colspan="6" class="text-muted">No stock rows for this week yet.</td></tr> }
        </tbody>
      </table>
    </div>

    <div class="mt-3 row g-2">
      <div class="col-md-4">
        <button class="btn btn-outline-primary btn-sm" (click)="applyTemplates()"><i class="bi bi-magic me-1"></i>Apply templates for this week</button>
      </div>
      <div class="col-md-8 d-flex align-items-end"><span class="text-muted small">Templates copy last saved quantities into this week's sheet.</span></div>
    </div>
  `,
})
export class FarmerStockComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  rows = signal<any[]>([]);
  stalls = signal<any[]>([]);
  products = signal<any[]>([]);
  week = signal('');
  prevWeek = '';
  nextWeek = '';

  sp: any = { product_id: '', farmer_market_id: '', quantity: '' };

  money = money; img = img; badge = statusBadge; label = statusLabel; onImageError = imageError;

  ngOnInit(): void {
    void this.load();
  }

  async load(): Promise<void> {
    const qs = this.week() ? '?week=' + this.week() : '';
    const d = await this.api.get<any>('/farmer/stock' + qs);
    this.rows.set(d.stocks);
    this.stalls.set(d.farmer_markets);
    this.products.set(d.products);
    this.week.set(d.week_start);
    this.prevWeek = d.prev_week;
    this.nextWeek = d.next_week;
    if (!this.sp.farmer_market_id && d.farmer_markets.length) this.sp.farmer_market_id = d.farmer_markets[0].id;
  }

  async saveStock(): Promise<void> {
    if (!this.sp.product_id || !this.sp.farmer_market_id || this.sp.quantity === '') {
      this.toast.error('Pick a product, a stall and a quantity.');
      return;
    }
    try {
      const res = await this.api.form<{ flash: Record<string, string> }>('/farmer/stock', {
        ...this.sp, week_start: this.week(),
      });
      this.toast.fromFlash(res.flash);
      this.sp.quantity = '';
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  async updateRow(s: any, quantity: number, status: string | null): Promise<void> {
    try {
      await this.api.form('/farmer/stock/' + s.id, {
        quantity, status: status ?? this.statusForAvailability(s, quantity),
      });
      this.toast.success('Stock updated.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
      void this.load();
    }
  }

  private statusForAvailability(s: any, q: number): string {
    if ((+q) <= 0) return 'unavailable';
    return s.status === 'available' ? 'available' : s.status;
  }

  async applyTemplates(): Promise<void> {
    const stall = this.stalls()[0];
    try {
      const res = await this.api.form<{ flash: Record<string, string> }>('/farmer/stock-templates/apply', {
        week_start: this.week(), farmer_market_id: stall?.id,
      });
      this.toast.fromFlash(res.flash);
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { date, formatTime, imageError, img, money } from '../../core/utils';

@Component({
  selector: 'app-checkout',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, FormsModule],
  template: `
    <h2 class="section-title mb-3">Checkout</h2>

    @if (groups().length) {
      @for (g of groups(); track g.farmer_market_id) {
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-transparent">
            <strong><i class="bi bi-shop me-1"></i>{{ g.farmer?.business_name }}</strong>
            <span class="text-muted small"> at {{ g.market?.name }}</span>
          </div>
          <div class="card-body">
            <table class="table table-sm align-middle mb-3">
              <tbody>
                @for (i of g.items; track i.id) {
                  <tr>
                    <td style="width:48px"><img [src]="img('product', i.product?.image)" (error)="onImageError($event, 'product')" class="rounded" style="width:44px;height:44px;object-fit:cover" alt=""></td>
                    <td>{{ i.product?.name }}</td>
                    <td class="text-muted small">{{ i.quantity }} {{ i.product?.unit ?? '' }} × {{ money(i.price) }}</td>
                    <td class="text-end fw-semibold">{{ money((+i.quantity) * (+i.price)) }}</td>
                  </tr>
                }
                <tr><td colspan="3" class="text-end fw-bold">Stall total</td><td class="text-end fw-bold text-success">{{ money(groupTotal(g)) }}</td></tr>
              </tbody>
            </table>

            <h6 class="fw-bold">Pickup slot</h6>
            @if (g.slots.length) {
              <div class="row row-cols-1 row-cols-md-2 g-2">
                @for (s of g.slots; track s.id) {
                  <label class="col d-flex">
                    <input class="me-2" type="radio" [name]="'slot-' + g.farmer_market_id" [checked]="slots()[g.farmer_market_id] === s.id" [value]="s.id" (change)="pick(g, s)">
                    <span class="border rounded p-2 flex-grow-1 small" [class.border-success]="slots()[g.farmer_market_id] === s.id">
                      <i class="bi bi-calendar-event me-1"></i>{{ date(s.pickup_date) }} {{ time(s.start_time) }}–{{ time(s.end_time) }}
                      <div class="text-secondary">Order by {{ date(s.cutoff_at, true) }}@if (s.max_orders) { · max {{ s.max_orders }} }</div>
                    </span>
                  </label>
                }
              </div>
            } @else {
              <div class="alert alert-warning mb-0 small">No pickup slots open for this stall yet — pick another time later or check back soon.</div>
            }
          </div>
        </div>
      }

      @if (unsellable().length) {
        <div class="alert alert-secondary small">
          These items are no longer available and were not counted: {{ unsellableList() }}
        </div>
      }

      <div class="row justify-content-end mt-3">
        <div class="col-lg-4">
          <div class="card shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between fs-5 fw-bold"><span>Total</span><span>{{ money(grandTotal()) }}</span></div>
              <div class="text-muted small mt-1">Pay in person at pickup.</div>
              <div class="mb-2"><label class="form-label small">Note to farmers (optional)</label><textarea class="form-control form-control-sm" name="note" [(ngModel)]="note" rows="2" maxlength="1000"></textarea></div>
              <button class="btn btn-ml w-100" (click)="place()" [disabled]="busy()">@if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }Place orders</button>
            </div>
          </div>
        </div>
      </div>
    } @else {
      <div class="alert alert-info">Nothing to check out. <a routerLink="/customer/cart">Back to cart</a>.</div>
    }
  `,
})
export class CheckoutComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  private router = inject(Router);

  groups = signal<any[]>([]);
  unsellable = signal<any[]>([]);
  slots = signal<Record<number, number>>({});
  busy = signal(false);
  note = '';

  money = money; img = img; date = date; time = formatTime; onImageError = imageError;

  ngOnInit(): void {
    void this.load();
  }

  async load(): Promise<void> {
    const d = await this.api.get<any>('/customer/checkout');
    this.groups.set(d.groups ?? []);
    this.unsellable.set(d.unsellable ?? []);
  }

  pick(g: any, s: any): void {
    this.slots.update((m) => ({ ...m, [g.farmer_market_id]: s.id }));
  }

  groupTotal(g: any): number {
    return g.items.reduce((s: number, i: any) => s + (+i.quantity) * (+i.price), 0);
  }

  grandTotal(): number {
    return this.groups().reduce((s: number, g: any) => s + this.groupTotal(g), 0);
  }

  unsellableList(): string {
    return this.unsellable().map((u) => u.product?.name).filter(Boolean).join(', ');
  }

  async place(): Promise<void> {
    const missing = this.groups().filter((g) => !this.slots()[g.farmer_market_id]);
    if (missing.length) {
      this.toast.error('Please choose a pickup slot for ' + missing.map((g) => g.market?.name).join(', ') + '.');
      return;
    }

    const form = new FormData();
    this.groups().forEach((g) => form.append('slots[' + g.farmer_market_id + ']', String(this.slots()[g.farmer_market_id])));
    if (this.note) form.append('note', this.note);

    this.busy.set(true);
    try {
      const res = await this.api.post<{ ok: boolean; flash: Record<string, string>; redirect: string }>('/customer/checkout/place', form, true);
      this.toast.fromFlash(res.flash);
      void this.router.navigateByUrl('/customer/orders');
    } catch (e) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }
}

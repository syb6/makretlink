import { ChangeDetectionStrategy, Component, computed, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { AuthService } from '../../core/auth.service';
import { ToastService } from '../../core/toast.service';
import { imageError, money, img } from '../../core/utils';

@Component({
  selector: 'app-cart',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink],
  template: `
    <h2 class="section-title mb-3">My cart</h2>

    @if (items().length === 0 && !loading()) {
      <div class="text-center py-5 text-muted">
        <i class="bi bi-cart-x fs-1"></i>
        <p class="mt-2">Your cart is empty.</p>
        <a routerLink="/products" class="btn btn-success">Browse products</a>
      </div>
    }

    <div class="card shadow-sm mb-3">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead><tr><th>Product</th><th>Where</th><th>Qty</th><th>Price</th><th>Subtotal</th><th></th></tr></thead>
          <tbody>
            @for (i of items(); track i.id) {
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <img [src]="img('product', i.product?.image)" (error)="onImageError($event, 'product')" class="rounded" style="width:42px;height:42px;object-fit:cover" alt="">
                    <div><div class="fw-semibold">{{ i.product?.name }}</div><div class="small text-muted">{{ i.product?.farmer?.business_name }}</div></div>
                  </div>
                </td>
                <td class="small">{{ i.farmerMarket?.market?.name }}<div class="text-secondary">{{ i.farmerMarket?.stall_name }}</div></td>
                <td>
                  <div class="input-group input-group-sm" style="width:120px">
                    <button class="btn btn-outline-secondary" (click)="setQty(i, (+i.quantity) - 0.5)">−</button>
                    <input class="form-control text-center" type="number" min="0.5" step="0.5" [value]="i.quantity" (change)="setQty(i, +$any($event.target).value)">
                    <button class="btn btn-outline-secondary" (click)="setQty(i, (+i.quantity) + 0.5)">+</button>
                  </div>
                </td>
                <td>{{ money(i.price) }}</td>
                <td class="fw-semibold">{{ money((+i.price) * (+i.quantity)) }}</td>
                <td class="text-end"><button class="btn btn-outline-danger btn-sm" (click)="remove(i)"><i class="bi bi-trash"></i></button></td>
              </tr>
            }
          </tbody>
        </table>
      </div>
    </div>

    @if (items().length > 0) {
      <div class="row justify-content-end">
        <div class="col-lg-4 col-md-6">
          <div class="card shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between fs-5 fw-bold"><span>Total</span><span>{{ total() }}</span></div>
              <a routerLink="/customer/checkout" class="btn btn-success w-100 mt-3">Go to checkout</a>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class CartComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  private auth = inject(AuthService);

  items = signal<any[]>([]);
  loading = signal(true);

  money = money; img = img; onImageError = imageError;

  total = computed(() => this.items().reduce((s, i) => s + (+i.price) * (+i.quantity), 0));

  ngOnInit(): void {
    void this.load();
  }

  async load(): Promise<void> {
    const d = await this.api.get<any>('/customer/cart');
    this.items.set(d.items);
    this.loading.set(false);
  }

  async setQty(i: any, q: number): Promise<void> {
    if (q < 0.5) q = 0.5;
    try {
      await this.api.form('/customer/cart/' + i.id + '/update', { quantity: q });
      i.quantity = q;
      this.toast.success('Cart updated.');
      void this.auth.load();
    } catch (e: any) {
      this.toast.fromValidation(e);
      void this.load();
    }
  }

  async remove(i: any): Promise<void> {
    try {
      await this.api.post('/customer/cart/' + i.id + '/remove');
      this.items.update((l) => l.filter((x) => x.id !== i.id));
      this.toast.success('Item removed.');
      void this.auth.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

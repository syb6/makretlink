import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../core/api.service';
import { AuthService } from '../core/auth.service';
import { ToastService } from '../core/toast.service';
import { imageError, img, money } from '../core/utils';
import { LoadingStateComponent } from '../layout/loading-state.component';

/** Mirror of products/show.blade.php — breadcrumb, availability box,
 *  single stock select + qty + add-to-cart, favorites, reviews, related. */
@Component({
  selector: 'app-product-detail',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, FormsModule, LoadingStateComponent],
  template: `
    @if (d(); as data) {
      <section class="py-4 py-md-5">
        <div class="container">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
              <li class="breadcrumb-item"><a routerLink="/products">Products</a></li>
              <li class="breadcrumb-item active">{{ data.product.name }}</li>
            </ol>
          </nav>

          <div class="row g-5">
            <div class="col-lg-6">
              <img [src]="img('product', data.product.image)" (error)="onImageError($event, 'product')"
                   class="w-100 rounded-4 shadow-sm" style="max-height:420px;object-fit:cover" [alt]="data.product.name">
            </div>
            <div class="col-lg-6">
              <span class="badge badge-soft mb-2">{{ data.product.category?.name ?? 'General' }}</span>
              <h1 class="section-title">{{ data.product.name }}</h1>
              <p class="text-muted">by
                <a [routerLink]="['/farmers', data.product.farmer?.id]" class="fw-semibold text-decoration-none">{{ data.product.farmer?.business_name }}</a>
              </p>

              <div class="d-flex align-items-center gap-2 mb-3">
                <span class="product-price fs-3">{{ money(data.product.price) }}</span>
                <span class="text-muted">/ {{ data.product.unit }}</span>
              </div>

              @if (data.avgRating) {
                <div class="mb-3">
                  <span class="stars">
                    @for (i of [1,2,3,4,5]; track i) {
                      <i class="bi {{ i <= round(data.avgRating) ? 'bi-star-fill' : 'bi-star' }}"></i>
                    }
                  </span>
                  <span class="small text-muted ms-1">{{ data.avgRating?.toFixed(1) }} ({{ data.reviewCount }} review{{ data.reviewCount === 1 ? '' : 's' }})</span>
                </div>
              }

              <p>{{ data.product.description }}</p>

              @if ((data.stocks?.length ?? 0) > 1) {
                <div class="market-availability">
                  <div class="market-availability-title">
                    <i class="bi bi-geo-alt-fill me-1"></i>Same product, available at {{ data.stocks.length }} markets
                  </div>
                  <ul class="market-availability-list">
                    @for (s of data.stocks; track s.id) {
                      <li>
                        <span>{{ s.market?.name }}</span>
                        <span class="text-muted small">{{ s.available }} {{ data.product.unit }} in stock</span>
                      </li>
                    }
                  </ul>
                  <p class="small text-muted mb-0 mt-2">
                    <i class="bi bi-info-circle me-1"></i>Pick whichever stall suits your week — stock is tracked per stall.
                  </p>
                </div>
              }

              @if (auth.isCustomer()) {
                <div class="card card-ml p-3 mb-3">
                  <label class="form-label fw-semibold small">Pick a stall / market stock</label>
                  <select name="stockId" [(ngModel)]="stockId" class="form-select mb-2" (change)="syncStock()">
                    @for (s of data.stocks; track s.id) {
                      <option [value]="s.id">
                        {{ s.market?.name }} — {{ s.stall_name ?? s.farmer_name ?? data.product.farmer?.business_name }}
                        ({{ s.available }} {{ data.product.unit }} available)
                      </option>
                    } @empty {
                      <option disabled>No stock available this week</option>
                    }
                  </select>
                  <div class="d-flex gap-2 align-items-center">
                    <input type="number" name="qty" class="form-control" style="max-width:110px"
                           min="0.5" step="0.5" [(ngModel)]="qtyInput">
                    <button class="btn btn-ml flex-grow-1" type="button" (click)="addToCart()" [disabled]="busy">
                      <i class="bi bi-cart-plus me-1"></i> Add to cart
                    </button>
                    <button class="btn btn-outline-danger" type="button" (click)="toggleFav()"
                            [attr.title]="isFav ? 'Remove from favorites' : 'Save to favorites'"
                            aria-label="Save to favorites">
                      <i class="bi {{ isFav ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                    </button>
                  </div>
                </div>
              } @else if (!auth.user()) {
                <a routerLink="/login" class="btn btn-ml btn-lg">Log in to pre-order</a>
              }
            </div>
          </div>

          <div class="mt-5">
            <h4 class="fw-bold mb-3">Customer reviews</h4>
            <div class="row g-3">
              @for (r of data.reviews; track r.id) {
                <div class="col-md-6">
                  <div class="card-ml p-3 h-100">
                    <div class="d-flex justify-content-between gap-2">
                      <strong>{{ r.customer }}</strong>
                      <span class="stars">
                        @for (i of [1,2,3,4,5]; track i) {
                          <i class="bi {{ i <= r.rating ? 'bi-star-fill' : 'bi-star' }} small"></i>
                        }
                      </span>
                    </div>
                    <p class="small mb-0 text-muted">{{ r.comment }}</p>
                  </div>
                </div>
              } @empty {
                <div class="col-12"><p class="text-muted small">No reviews yet. Reviews can be left after completing an order.</p></div>
              }
            </div>
          </div>

          @if (data.related?.length) {
            <h4 class="fw-bold mt-5 mb-3">Related products</h4>
            <div class="row g-4">
              @for (p of data.related; track p.id) {
                <div class="col-6 col-md-3">
                  <a [routerLink]="['/products', p.id]" class="text-decoration-none">
                    <div class="product-card h-100">
                      <img [src]="img('product', p.image)" (error)="onImageError($event, 'product')" class="product-thumb" [alt]="p.name">
                      <div class="p-2">
                        <div class="fw-semibold small">{{ p.name }}</div>
                        <span class="product-price small">{{ money(p.price) }}</span>
                      </div>
                    </div>
                  </a>
                </div>
              }
            </div>
          }
        </div>
      </section>
    } @else {
      @if (loaded()) {
        <div class="alert alert-warning m-4" role="alert">This product could not be loaded. Please try again.</div>
      } @else {
        <app-loading-state label="Loading product…" />
      }
    }
  `,
})
export class ProductDetailComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  readonly auth = inject(AuthService);
  private route = inject(ActivatedRoute);

  d = signal<any>(null);
  loaded = signal(false);
  stockId: number | string = '';
  qtyInput: string | number = 1;
  busy = false;

  isFav = false;
  money = money; img = img; onImageError = imageError;
  round = Math.round;

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    this.api.get<any>('/products/' + id).then((x) => {
      this.d.set(x);
      const first = x.stocks?.[0];
      if (first) {
        this.stockId = first.id;
      }
      this.isFav = (x.favoriteProductIds ?? []).includes(Number(id));
    }).catch((error: unknown) => this.toast.fromValidation(error))
      .finally(() => this.loaded.set(true));
  }

  syncStock(): void { /* stock select drives addToCart(); keeps parity with the blade data-stock-id swap */ }

  async addToCart(): Promise<void> {
    if (!this.stockId) return;
    this.busy = true;
    try {
      await this.api.form('/customer/cart/add', { stock_id: this.stockId, quantity: this.qtyInput || 1 });
      this.toast.success('Added to cart.');
      void this.auth.load();
    } catch (e) {
      this.toast.fromValidation(e);
    } finally {
      this.busy = false;
    }
  }

  async toggleFav(): Promise<void> {
    try {
      const res = await this.api.form<{ state: string }>('/customer/favorites/product', { product_id: this.d().product.id });
      this.isFav = res.state === 'added';
      if (this.isFav) this.toast.success('Saved to favorites ♥');
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

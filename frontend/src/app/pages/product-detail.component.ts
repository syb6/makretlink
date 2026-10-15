import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ApiService } from '../core/api.service';
import { AuthService } from '../core/auth.service';
import { ToastService } from '../core/toast.service';
import { date, imageError, img, money } from '../core/utils';

@Component({
  selector: 'app-product-detail',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink],
  template: `
    @if (d(); as data) {
      <div class="detail-panel">
      <div class="row g-4">
        <div class="col-lg-5">
          <img [src]="img('product', data.product.image)" (error)="onImageError($event, 'product')" class="w-100 rounded-4 shadow-sm" [alt]="data.product.name">
        </div>
        <div class="col-lg-7">
          <h2 class="fw-bold">{{ data.product.name }}</h2>
          <div class="rating-stars mb-2">
            @for (i of [1,2,3,4,5]; track i) {
              <i class="bi" [class.bi-star-fill]="i <= round(data.avgRating)" [class.bi-star]="i > round(data.avgRating)"></i>
            }
            <span class="small text-muted ms-1">{{ data.reviewCount }} review(s)</span>
          </div>
          <div class="text-success fw-bold fs-3 mb-2">{{ money(data.product.price) }} <small class="text-muted fs-6 fw-normal">/ {{ data.product.unit }}</small></div>
          <p class="text-muted">{{ data.product.description }}</p>
          <div class="small text-muted mb-3">
            Sold by <a [routerLink]="['/farmers', data.product.farmer?.id]">{{ data.product.farmer?.business_name }}</a>
            @if (data.product.farmer?.markets?.length) {
              · @for (m of data.product.farmer.markets; track m.id) { <span class="badge bg-light text-dark me-1">{{ m.market?.name }}</span> }
            }
          </div>

          <h6 class="fw-bold">Buy this week at</h6>
          <div class="list-group mb-3">
            @for (s of data.stocks; track s.id) {
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <strong>{{ s.market?.name }}</strong>
                  @if (s.stall_name) { <span class="text-muted small"> · {{ s.stall_name }}</span> }
                  <div class="small text-secondary">{{ s.available }} {{ s.unit }} left</div>
                </div>
                @if (auth.isCustomer()) {
                  <div class="d-flex align-items-center gap-2">
                    <input class="form-control form-control-sm" type="number" min="0.5" step="0.5" [value]="qty()" (input)="qty.set(+$any($event.target).value)" style="width: 72px">
                    <button class="btn btn-success btn-sm" (click)="addToCart(s)">Add</button>
                  </div>
                }
              </div>
            } @empty {
              <div class="list-group-item text-muted">Currently unavailable — check back next week.</div>
            }
          </div>
        </div>
      </div>
      </div>

      <h2 class="section-title mt-5 mb-3">Customer reviews</h2>
      @for (r of data.reviews; track r.id) {
        <div class="border-bottom pb-2 mb-2">
          <div class="rating-stars">
            @for (i of [1,2,3,4,5]; track i) { <i class="bi" [class.bi-star-fill]="i <= r.rating" [class.bi-star]="i > r.rating"></i> }
          </div>
          <div class="small fw-semibold">{{ r.customer }}</div>
          <div class="small text-muted">{{ r.comment }} · {{ date(r.created_at) }}</div>
        </div>
      } @empty {
        <p class="text-muted">No reviews yet.</p>
      }

      @if (data.related.length) {
        <h2 class="section-title mt-5 mb-3">More from this category</h2>
        <div class="row g-3">
          @for (p of data.related; track p.id) {
            <div class="col-6 col-md-3">
              <a [routerLink]="['/products', p.id]" class="card text-decoration-none text-reset h-100 shadow-sm">
                <img [src]="img('product', p.image)" (error)="onImageError($event, 'product')" class="card-img-top card-img-cover" [alt]="p.name">
                <div class="card-body py-2">
                  <div class="small fw-semibold text-truncate">{{ p.name }}</div>
                  <div class="small text-success fw-bold">{{ money(p.price) }}</div>
                </div>
              </a>
            </div>
          }
        </div>
      }
    } @else {
      <div class="text-center py-5"><div class="spinner-border text-success"></div></div>
    }
  `,
})
export class ProductDetailComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  readonly auth = inject(AuthService);
  private route = inject(ActivatedRoute);

  d = signal<any>(null);
  qty = signal(1);

  money = money; img = img; date = date; onImageError = imageError;
  round = Math.round;

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    this.api.get<any>('/products/' + id).then((x) => this.d.set(x));
  }

  async addToCart(s: any): Promise<void> {
    try {
      await this.api.form('/customer/cart/add', { stock_id: s.id, quantity: this.qty() });
      this.toast.success('Added to cart.');
      void this.auth.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

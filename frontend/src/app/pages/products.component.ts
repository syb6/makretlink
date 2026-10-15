import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ApiService } from '../core/api.service';
import { AuthService } from '../core/auth.service';
import { ToastService } from '../core/toast.service';
import { imageError, money, img } from '../core/utils';
import { PersonAvatarComponent } from '../core/person-avatar.component';

@Component({
  selector: 'app-products',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, FormsModule, PersonAvatarComponent],
  template: `
    <section class="page-banner">
      <div class="container">
        <span class="badge-sub">FRESH FROM LOCAL FARMERS</span>
        <h1 class="section-title">Browse <span class="accent">Products</span></h1>
        <p class="section-subtitle">Find seasonal produce and reserve it for pickup at your local market.</p>
        <i class="bi bi-basket2 page-banner-icon" aria-hidden="true"></i>
      </div>
    </section>

    <div class="container py-4 py-lg-5">
      <form class="card filter-card mb-4" autocomplete="off" (ngSubmit)="applyFilters()">
        <div class="card-body row g-3 align-items-end">
          <div class="col-lg-3 col-md-6">
            <label class="form-label small mb-1" for="product-search">Search</label>
            <input id="product-search" class="form-control" name="q" [(ngModel)]="f.q" placeholder="Tomatoes, honey…">
          </div>
          <div class="col-lg-2 col-md-3 col-6">
            <label class="form-label small mb-1" for="product-category">Category</label>
            <select id="product-category" class="form-select" name="category" [(ngModel)]="f.category">
              <option value="">All categories</option>
              @for (c of cats(); track c.id) { <option [value]="c.id">{{ c.name }}</option> }
            </select>
          </div>
          <div class="col-lg-2 col-md-3 col-6">
            <label class="form-label small mb-1" for="product-market">Market</label>
            <select id="product-market" class="form-select" name="market" [(ngModel)]="f.market">
              <option value="">All markets</option>
              @for (m of markets(); track m.id) { <option [value]="m.id">{{ m.name }}</option> }
            </select>
          </div>
          <div class="col-lg-2 col-6">
            <label class="form-label small mb-1" for="product-max-price">Max price</label>
            <input id="product-max-price" type="number" min="0" class="form-control" name="max_price" [(ngModel)]="f.max_price">
          </div>
          <div class="col-lg-2 col-6">
            <label class="form-label small mb-1" for="product-sort">Sort</label>
            <select id="product-sort" class="form-select" name="sort" [(ngModel)]="f.sort">
              <option value="name">Name</option>
              <option value="price_low">Price low to high</option>
              <option value="price_high">Price high to low</option>
              <option value="newest">Newest</option>
            </select>
          </div>
          <div class="col-lg-1 d-grid"><button type="submit" class="btn btn-ml">Apply</button></div>
        </div>
      </form>

      <p class="text-muted mb-3">{{ stocks().length }} products on this page</p>
      <div class="row g-4">
        @for (s of stocks(); track s.id; let index = $index) {
          <div class="col-6 col-md-4 col-xl-3">
            <article class="product-card">
              <div class="product-img-wrap">
                <a [routerLink]="['/products', s.product.id]" [attr.aria-label]="'View ' + s.product.name">
                  <img [src]="img('product', s.product.image)" (error)="onImageError($event, 'product')"
                       [alt]="s.product.name" width="600" height="400"
                       [attr.loading]="index < 4 ? 'eager' : 'lazy'"
                       [attr.fetchpriority]="index === 0 ? 'high' : 'auto'">
                </a>
                <span class="product-badge">{{ s.product.category?.name ?? 'Fresh produce' }}</span>
                @if (auth.isCustomer()) {
                  <button class="fav-btn" type="button" (click)="toggleFav(s)"
                          [attr.aria-label]="isFav(s) ? 'Remove from favorites' : 'Save to favorites'">
                    <i class="bi" [class.bi-heart-fill]="isFav(s)" [class.bi-heart]="!isFav(s)"></i>
                  </button>
                }
              </div>
              <div class="product-body">
                <span class="product-cat">{{ s.product.category?.name ?? 'General' }}</span>
                <a [routerLink]="['/products', s.product.id]" class="product-name">{{ s.product.name }}</a>
                <div class="product-farmer">
                  <app-person-avatar [name]="s.farmer?.name || s.farmer?.business_name || 'Local farmer'"
                                     [src]="s.farmer?.avatar || s.farmer?.profile_image"
                                     class="avatar-sm rounded-circle me-1" />
                  {{ s.farmer?.business_name ?? 'Local farmer' }}
                  <span class="d-block"><i class="bi bi-geo-alt me-1"></i>{{ s.farmerMarket?.market?.name ?? 'Local market' }}</span>
                </div>
                <div class="product-bottom">
                  <span class="product-price">{{ money(s.product.price) }} <small>/{{ s.product.unit }}</small></span>
                  @if (auth.isCustomer()) {
                    <button class="product-add" type="button" (click)="addToCart(s)" [attr.aria-label]="'Add ' + s.product.name + ' to cart'"><i class="bi bi-plus-lg"></i></button>
                  } @else {
                    <a [routerLink]="['/products', s.product.id]" class="product-add" [attr.aria-label]="'View ' + s.product.name"><i class="bi bi-arrow-right"></i></a>
                  }
                </div>
              </div>
            </article>
          </div>
        } @empty {
          <div class="col-12"><div class="ml-note"><i class="bi bi-info-circle"></i>Nothing matches those filters yet.</div></div>
        }
      </div>

      @if (lastPage() > 1) {
        <nav class="mt-4" aria-label="Product pages">
          <ul class="pagination justify-content-center">
            <li class="page-item" [class.disabled]="page() <= 1"><button class="page-link" type="button" (click)="go(page() - 1)" aria-label="Previous page">‹</button></li>
            @for (p of pages(); track p) {
              <li class="page-item" [class.active]="p === page()"><button class="page-link" type="button" (click)="go(p)" [attr.aria-current]="p === page() ? 'page' : null">{{ p }}</button></li>
            }
            <li class="page-item" [class.disabled]="page() >= lastPage()"><button class="page-link" type="button" (click)="go(page() + 1)" aria-label="Next page">›</button></li>
          </ul>
        </nav>
      }
    </div>
  `,
})
export class ProductsComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  private route = inject(ActivatedRoute);
  readonly auth = inject(AuthService);

  f: any = { q: '', category: '', market: '', max_price: '', sort: 'name' };
  stocks = signal<any[]>([]);
  cats = signal<any[]>([]);
  markets = signal<any[]>([]);
  favs = signal<Set<number>>(new Set());
  page = signal(1);
  lastPage = signal(1);

  money = money;
  img = img;
  onImageError = imageError;

  ngOnInit(): void {
    const q = this.route.snapshot.queryParamMap;
    this.f = { ...this.f, category: q.get('category') ?? '', market: q.get('market') ?? '' };
    void this.load();
  }

  pages(): number[] {
    return Array.from({ length: this.lastPage() }, (_, i) => i + 1);
  }

  applyFilters(): void {
    this.page.set(1);
    void this.load();
  }

  async load(): Promise<void> {
    const params = new URLSearchParams();
    for (const [k, v] of Object.entries(this.f)) {
      if (v !== '' && v !== null) params.set(k, String(v));
    }
    params.set('page', String(this.page()));
    try {
      const d = await this.api.get<any>('/products?' + params.toString());
      this.stocks.set(d.data);
      this.cats.set(d.categories);
      this.markets.set(d.markets);
      this.favs.set(new Set(d.favoriteProductIds ?? []));
      this.page.set(d.page);
      this.lastPage.set(d.last_page);
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  go(p: number): void {
    if (p < 1 || p > this.lastPage()) return;
    this.page.set(p);
    void this.load();
  }

  isFav(s: any): boolean {
    return this.favs().has(s.product.id);
  }

  async toggleFav(s: any): Promise<void> {
    try {
      const res = await this.api.form<{ state: string }>('/customer/favorites/product', { product_id: s.product.id });
      this.favs.update((f) => {
        const n = new Set(f);
        res.state === 'added' ? n.add(s.product.id) : n.delete(s.product.id);
        return n;
      });
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  async addToCart(s: any): Promise<void> {
    try {
      await this.api.form('/customer/cart/add', { stock_id: s.id, quantity: 1 });
      this.toast.success(s.product.name + ' added to cart.');
      void this.auth.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

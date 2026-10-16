import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ApiService } from '../core/api.service';
import { AuthService } from '../core/auth.service';
import { ToastService } from '../core/toast.service';
import { imageError, money, img, limit } from '../core/utils';
import { PersonAvatarComponent } from '../core/person-avatar.component';

@Component({
  selector: 'app-products',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, FormsModule, PersonAvatarComponent],
  template: `
    <section class="page-banner anim-up">
      <i class="bi bi-basket2-fill page-banner-icon" aria-hidden="true"></i>
      <div class="container">
        <span class="badge-sub">FRESH ARRIVALS</span>
        <h1 class="section-title mb-1">Browse Products</h1>
        <p class="section-subtitle mb-0">Live weekly stock — filter by category, market, day and price.</p>
      </div>
    </section>

    <div class="container py-4 py-lg-5">
      <form class="card card-ml p-3 mb-4 product-filter-card" autocomplete="off" (ngSubmit)="applyFilters()">
        <div class="row g-2 align-items-end">
          <div class="col-lg-3 col-md-4 col-6">
            <label class="form-label small fw-semibold mb-1" for="filterQ">Search</label>
            <input type="text" name="q" id="filterQ" class="form-control form-control-sm" placeholder="Tomatoes, honey..." [(ngModel)]="f.q">
          </div>
          <div class="col-lg-2 col-md-2 col-6">
            <label class="form-label small fw-semibold mb-1" for="filterCategory">Category</label>
            <select name="category" id="filterCategory" class="form-select form-select-sm" (change)="applyFilters()" [(ngModel)]="f.category">
              <option value="">All</option>
              @for (c of cats(); track c.id) { <option [value]="c.id">{{ c.name }}</option> }
            </select>
          </div>
          <div class="col-lg-2 col-md-2 col-6">
            <label class="form-label small fw-semibold mb-1" for="filterMarket">Market</label>
            <select name="market" id="filterMarket" class="form-select form-select-sm" (change)="applyFilters()" [(ngModel)]="f.market">
              <option value="">All</option>
              @for (m of markets(); track m.id) { <option [value]="m.id">{{ m.name }}</option> }
            </select>
          </div>
          <div class="col-lg-2 col-md-2 col-6">
            <label class="form-label small fw-semibold mb-1" for="filterDay">Day</label>
            <select name="day" id="filterDay" class="form-select form-select-sm" (change)="applyFilters()" [(ngModel)]="f.day">
              <option value="">Any</option>
              @for (label of days(); track $index; let i = $index) { <option [value]="i">{{ label }}</option> }
            </select>
          </div>
          <div class="col-lg-1 col-md-2 col-3">
            <label class="form-label small fw-semibold mb-1" for="filterMin">Min Rs</label>
            <input type="number" step="0.01" min="0" name="min_price" id="filterMin" class="form-control form-control-sm" [(ngModel)]="f.min_price">
          </div>
          <div class="col-lg-1 col-md-2 col-3">
            <label class="form-label small fw-semibold mb-1" for="filterMax">Max Rs</label>
            <input type="number" step="0.01" min="0" name="max_price" id="filterMax" class="form-control form-control-sm" [(ngModel)]="f.max_price">
          </div>
          <div class="col-lg-1 col-md-2 col-6 d-grid">
            <button class="btn btn-ml btn-sm">Go</button>
          </div>
        </div>
      </form>

      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <span class="text-muted small">{{ total() }} result(s)</span>
        <form class="d-flex gap-2 align-items-center" (ngSubmit)="load()">
          <label class="small text-muted" for="sortSelect">Sort</label>
          <select name="sort" id="sortSelect" class="form-select form-select-sm w-auto" [(ngModel)]="f.sort" (change)="applyFilters()">
            <option value="name">Name</option>
            <option value="price_low">Price: low to high</option>
            <option value="price_high">Price: high to low</option>
            <option value="newest">Newest</option>
          </select>
        </form>
      </div>

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
                <span class="product-badge">{{ (s.available ?? 0) > 0 ? 'In Stock' : 'Sold Out' }}</span>
                @if ((marketCounts()[s.product.id] ?? 1) > 1) {
                  <span class="product-badge product-badge-alt"><i class="bi bi-geo-alt-fill me-1"></i>{{ marketCounts()[s.product.id] }} markets</span>
                }
                @if (auth.isCustomer()) {
                  <button class="fav-btn" type="button" (click)="toggleFav(s)"
                          [attr.aria-label]="isFav(s) ? 'Remove from favorites' : 'Save to favorites'">
                    <i class="bi" [class.bi-heart-fill]="isFav(s)" [class.bi-heart]="!isFav(s)"></i>
                  </button>
                }
              </div>                <div class="product-body">
                  <span class="product-cat">{{ s.product.category?.name ?? 'General' }}</span>
                  <a [routerLink]="['/products', s.product.id]" class="product-name">{{ s.product.name }}</a>
                  <div class="product-farmer">
                    <app-person-avatar [name]="s.farmer?.name || s.farmer?.business_name || 'Local farmer'"
                                       [src]="s.farmer?.avatar || s.farmer?.profile_image"
                                       class="avatar-sm rounded-circle me-1" />
                    {{ s.farmer?.business_name ?? 'Local farmer' }}
                  <span class="d-block"><i class="bi bi-geo-alt me-1"></i>{{ limit(s.farmerMarket?.market?.name, 26) }}@if ((marketCounts()[s.product.id] ?? 1) > 1) { · +{{ marketCounts()[s.product.id] - 1 }} more }@endif</span>
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
          <div class="col-12"><div class="ml-note"><i class="bi bi-info-circle"></i>No products match your filters.</div></div>
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

  f: any = { q: '', category: '', market: '', day: '', min_price: '', max_price: '', sort: 'name' };
  stocks = signal<any[]>([]);
  cats = signal<any[]>([]);
  markets = signal<any[]>([]);
  days = signal<string[]>([]);
  favs = signal<Set<number>>(new Set());
  counts = signal<Record<string, number>>({});
  page = signal(1);
  lastPage = signal(1);
  total = signal(0);

  money = money;
  img = img;
  limit = limit;
  onImageError = imageError;

  marketCounts(): Record<string, number> {
    return this.counts();
  }

  ngOnInit(): void {
    const q = this.route.snapshot.queryParamMap;
    this.f = {
      ...this.f,
      category: q.get('category') ?? '',
      market: q.get('market') ?? '',
      day: q.get('day') ?? '',
      min_price: q.get('min_price') ?? '',
      max_price: q.get('max_price') ?? '',
      sort: q.get('sort') ?? 'name',
    };
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
      this.days.set(d.days ? Object.values(d.days) : []);
      this.counts.set(d.marketCounts ?? {});
      this.favs.set(new Set(d.favoriteProductIds ?? []));
      this.page.set(d.page);
      this.lastPage.set(d.last_page);
      this.total.set(d.total ?? d.data.length);
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

import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../core/auth.service';
import { ApiService } from '../core/api.service';
import { ToastService } from '../core/toast.service';
import { date, dayName, formatTime, imageError, imageLoaded, img, limit, money, openDaysLabel } from '../core/utils';
import { PersonAvatarComponent } from '../core/person-avatar.component';
interface HomeMarketSchedule { id: number; day_of_week: number; is_closed: boolean; opening_time: string; closing_time: string; }

interface HomeStock {
  id: number;
  product: {
    id: number;
    name: string;
    image: string | null;
    price: number;
    unit: string;
    category?: { name: string } | null;
  };
  farmer?: { business_name: string; name?: string | null; avatar?: string | null; profile_image?: string | null } | null;
  farmerMarket?: { market?: { name: string } | null } | null;
  available?: number;
}

interface HomeMarket {
  id: number;
  name: string;
  address: string;
  description?: string | null;
  image?: string | null;
  farmers_count?: number;
  schedules?: { id: number; day_of_week: number; is_closed: boolean; opening_time: string; closing_time: string }[];
}

interface HomeData {
  announcements: { id: number; title: string; message: string; published_at: string }[];
  featuredStocks: HomeStock[];
  marketCounts: Record<string, number>;
  marketCount: number;
  farmerCount: number;
  productCount: number;
  categories: { id: number; name: string; slug: string; image: string | null }[];
  favoriteProductIds: number[];
  markets: HomeMarket[];
}

@Component({
  selector: 'app-home',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, PersonAvatarComponent],
  template: `
    <section class="hero-section" id="home">
      <div class="hero-bg-photo" aria-hidden="true">
        <img src="/images/hero-bg.webp" alt="" width="1600" height="900" fetchpriority="high" decoding="async" (load)="onImageLoad($event)">
      </div>
      <div class="hero-bg-scrim" aria-hidden="true"></div>
      <div class="hero-bg-glow" aria-hidden="true"></div>
      <div class="hero-bg-glow-2" aria-hidden="true"></div>

      <div class="container hero-grid">
        <div class="hero-content">
          <span class="badge-sub ml-in" style="--d: 60ms">LOCAL · FRESH · COMMUNITY</span>
          <h1 class="hero-title" aria-label="Fresh From Local Farmers, Made Easy for You.">
            <span class="w" style="--i: 0" aria-hidden="true">Fresh</span>
            <span class="w" style="--i: 1" aria-hidden="true">From</span>
            <span class="w accent" style="--i: 2" aria-hidden="true">Local</span>
            <span class="w" style="--i: 3" aria-hidden="true">Farmers,</span>
            <span class="w" style="--i: 4" aria-hidden="true">Made</span>
            <span class="w accent" style="--i: 5" aria-hidden="true">Easy</span>
            <span class="w" style="--i: 6" aria-hidden="true">for</span>
            <span class="w" style="--i: 7" aria-hidden="true">You.</span>
          </h1>
          <p class="hero-description ml-in" style="--d: 280ms">
            Discover local farmers, explore fresh weekly produce, and reserve what you need online for convenient, hassle-free market pickup.
          </p>
          <div class="hero-btns ml-in" style="--d: 400ms">
            <a routerLink="/markets" class="btn btn-ml btn-lg-ml">Explore Markets <i class="bi bi-arrow-right"></i></a>
            <a routerLink="/products" class="btn btn-outline-ml btn-lg-ml">Browse Products</a>
          </div>

          <div class="hero-stats ml-in" style="--d: 540ms" role="list" aria-label="MarketLink in numbers">
            <div class="hero-stat" role="listitem">
              <span class="hero-stat-number stat-number">{{ data()?.marketCount ?? 0 }}</span>
              <span class="hero-stat-label">Active Markets</span>
            </div>
            <div class="hero-stat" role="listitem">
              <span class="hero-stat-number stat-number">{{ data()?.farmerCount ?? 0 }}</span>
              <span class="hero-stat-label">Local Farmers</span>
            </div>
            <div class="hero-stat" role="listitem">
              <span class="hero-stat-number stat-number">{{ data()?.productCount ?? 0 }}</span>
              <span class="hero-stat-label">Fresh Products</span>
            </div>
          </div>

          @if (data()?.announcements?.length) {
            <div class="hero-announcements ml-in" style="--d: 660ms">
              <div class="hero-announcements-head"><i class="bi bi-megaphone-fill"></i><span>Latest Announcements</span></div>
              <div class="hero-announcements-body">
                @for (announcement of data()?.announcements ?? []; track announcement.id) {
                  <div class="hero-announcement">
                    <span class="hero-announcement-date">{{ date(announcement.published_at) }}</span>
                    <span class="hero-announcement-text">{{ announcement.title }}</span>
                  </div>
                }
              </div>
            </div>
          }
        </div>

        <div class="hero-image-wrapper text-center ml-in" style="--d: 300ms">
          <img src="/images/hero-basket.webp" alt="A basket of fresh vegetables from local farms"
               class="hero-img" width="800" height="533" fetchpriority="high" (load)="onImageLoad($event)">
        </div>
      </div>

      <div class="hero-chips-strip" role="presentation">
        <span class="hero-float-chips chip-1" aria-hidden="true"><i class="bi bi-basket2-fill"></i> 1,200+ baskets reserved</span>
        <span class="hero-float-chips chip-2" aria-hidden="true"><i class="bi bi-star-fill"></i> 9 farms near you</span>
        <span class="hero-float-chips chip-3" aria-hidden="true"><i class="bi bi-patch-check-fill"></i> 100% verified farmers</span>
      </div>
      <a class="hero-scroll-cue" href="#features" aria-label="Scroll to features"><span>Scroll</span><i class="bi bi-chevron-double-down"></i></a>
    </section>

    <div class="hero-blend" aria-hidden="true"></div>

    <section class="features-section" id="features">
      <div class="container">
        <div class="features-grid">
          <div class="feature-card"><div class="feature-icon-circle"><i class="bi bi-flower2"></i></div><div class="feature-info"><h4>Fresh Local Products</h4><p>Discover products directly from verified local farmers and markets.</p></div></div>
          <div class="feature-card"><div class="feature-icon-circle"><i class="bi bi-cart-check"></i></div><div class="feature-info"><h4>Easy Pre-Orders</h4><p>Reserve seasonal produce online before market day sells out.</p></div></div>
          <div class="feature-card"><div class="feature-icon-circle"><i class="bi bi-shop"></i></div><div class="feature-info"><h4>Convenient Pickup</h4><p>Collect your items directly at the market stall and pay in person.</p></div></div>
        </div>
      </div>
    </section>

    <section class="section-padding">
      <div class="container">
        <div class="section-head">
          <span class="badge-sub">ORGANIC SELECTION</span>
          <h2 class="section-title">Explore <span class="accent">Categories</span></h2>
          <p class="section-subtitle">Browse farm-fresh categories directly from community growers.</p>
        </div>
        <div class="categories-grid">
          @for (category of data()?.categories ?? []; track category.id) {
            <a routerLink="/products" [queryParams]="{ category: category.id }" class="category-card">
              <div class="category-img-circle">
                <img [src]="img('category', category.image)" (error)="onImageError($event, 'category')"
                     [alt]="category.name" width="106" height="106" loading="lazy">
              </div>
              <h3 class="category-title">{{ category.name }}</h3>
            </a>
          } @empty {
            <p class="text-muted">No categories yet.</p>
          }
        </div>
      </div>
    </section>

    <section class="section-padding" id="markets" style="background-color: var(--light-bg)">
      <div class="container">
        <div class="section-head">
          <span class="badge-sub">COMMUNITY HUBS</span>
          <h2 class="section-title">Explore <span class="accent">Local Markets</span></h2>
          <p class="section-subtitle">Find participating weekly markets, check their days, and pre-order fresh produce.</p>
        </div>
        <div class="markets-grid">
          @for (market of data()?.markets ?? []; track market.id) {
            <article class="market-card">
              <div class="market-img-box">
                <span class="market-status-tag">{{ openDaysLabel(market.schedules) }}</span>
                <img [src]="img('market', market.image)" (error)="onImageError($event, 'market')"
                     [alt]="market.name" width="600" height="400" loading="lazy">
              </div>
              <div class="market-details">
                <h3 class="market-title">{{ market.name }}</h3>
                <div class="market-meta">
                  <span><i class="bi bi-geo-alt-fill"></i> {{ market.address }}</span>
                  @if (firstOpen(market)) {
                    <span><i class="bi bi-clock-fill"></i> {{ dayName(firstOpen(market)!.day_of_week) }}: {{ time(firstOpen(market)!.opening_time) }} - {{ time(firstOpen(market)!.closing_time) }}</span>
                  }
                </div>
                @if (openCount(market) > 1) {
                  <p class="market-desc mb-2">
                    <span class="multi-market-note"><i class="bi bi-calendar-week me-1"></i>Trades {{ openCount(market) }} days weekly — {{ openNames(market) }}</span>
                  </p>
                }
                <p class="market-desc">{{ limit(market.description || 'A community market with local growers and artisans.', 110) }}</p>
                <div class="market-bottom">
                  <span class="vendor-count"><i class="bi bi-shop me-1"></i> {{ market.farmers_count ?? 0 }} Local Farmer{{ (market.farmers_count ?? 0) === 1 ? '' : 's' }}</span>
                  <a [routerLink]="['/markets', market.id]" class="btn-explore-market">View Market</a>
                </div>
              </div>
            </article>
          } @empty {
            <div class="ml-note"><i class="bi bi-info-circle"></i>No active markets yet. Check back soon!</div>
          }
        </div>
        <div class="text-center mt-4">
          <a routerLink="/markets" class="btn btn-outline-ml">View All Markets <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
      </div>
    </section>

    <div class="section-divider-leaf" aria-hidden="true"><i class="bi bi-flower1"></i></div>

    <section class="section-padding" id="products">
      <div class="container">
        <div class="section-head">
          <span class="badge-sub">FRESH ARRIVALS</span>
          <h2 class="section-title">Farm <span class="accent">Fresh Picks</span></h2>
          <p class="section-subtitle">A sample of what's currently listed by farmers across MarketLink this week.</p>
        </div>
        <div class="row g-4">
          @for (stock of data()?.featuredStocks ?? []; track stock.id) {
            <div class="col-sm-6 col-md-4 col-lg-3">
              <article class="product-card">
                <div class="product-img-wrap">
                  <a [routerLink]="['/products', stock.product.id]" [attr.aria-label]="'View ' + stock.product.name">
                    <img [src]="img('product', stock.product.image)" (error)="onImageError($event, 'product')"
                         [alt]="stock.product.name" width="600" height="400" loading="lazy">
                  </a>
                  <span class="product-badge">{{ (stock.available ?? 0) > 0 ? 'In Stock' : 'Sold Out' }}</span>
                  @if ((data()?.marketCounts?.[stock.product.id] ?? 1) > 1) {
                    <span class="product-badge product-badge-alt"><i class="bi bi-geo-alt-fill me-1"></i>{{ data()?.marketCounts?.[stock.product.id] }} markets</span>
                  }
                  @if (auth.isCustomer()) {
                    <button class="fav-btn" type="button" (click)="toggleFav(stock)"
                            [attr.aria-label]="isFav(stock) ? 'Remove from favorites' : 'Save to favorites'">
                      <i class="bi" [class.bi-heart-fill]="isFav(stock)" [class.bi-heart]="!isFav(stock)"></i>
                    </button>
                  }
                </div>
                <div class="product-body">
                  <span class="product-cat">{{ stock.product.category?.name ?? 'General' }}</span>
                  <a [routerLink]="['/products', stock.product.id]" class="product-name">{{ stock.product.name }}</a>
                  <div class="product-farmer">
                    <app-person-avatar [name]="stock.farmer?.name || stock.farmer?.business_name || 'Local farmer'"
                                       [src]="stock.farmer?.avatar || stock.farmer?.profile_image"
                                       class="avatar-sm rounded-circle me-1" />
                    {{ stock.farmer?.business_name ?? 'Local farmer' }}
                    <span class="d-block"><i class="bi bi-geo-alt me-1"></i>{{ limit(stock.farmerMarket?.market?.name, 26) }}@if ((data()?.marketCounts?.[stock.product.id] ?? 1) > 1) { · +{{ (data()?.marketCounts?.[stock.product.id] ?? 1) - 1 }} more }@endif</span>
                  </div>
                  <div class="product-bottom">
                    <span class="product-price">{{ money(stock.product.price) }} <small>/{{ stock.product.unit }}</small></span>
                    @if (auth.isCustomer()) {
                      <button class="product-add" type="button" (click)="addToCart(stock)" [attr.aria-label]="'Add ' + stock.product.name + ' to cart'"><i class="bi bi-plus-lg"></i></button>
                    } @else {
                      <a [routerLink]="['/products', stock.product.id]" class="product-add" aria-label="View product"><i class="bi bi-arrow-right"></i></a>
                    }
                  </div>
                </div>
              </article>
            </div>
          } @empty {
            <div class="col-12"><div class="ml-note"><i class="bi bi-info-circle"></i>No weekly stock has been published yet. Check back soon!</div></div>
          }
        </div>
        <div class="text-center mt-4">
          <a routerLink="/products" class="btn btn-outline-ml">View All Products <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
      </div>
    </section>

    <section class="section-padding hiw-section" style="background-color: var(--light-bg)">
      <div class="container">
        <div class="section-head">
          <span class="badge-sub">SIMPLE PROCESS</span>
          <h2 class="section-title">How <span class="accent">MarketLink</span> Works</h2>
          <p class="section-subtitle">A simple way to support local farmers and get fresh market produce.</p>
        </div>
        <div class="steps-grid">
          <div class="step-card"><div class="step-number">01</div><h3>Discover</h3><p>Find nearby farmers markets and local participating growers.</p></div>
          <div class="step-card"><div class="step-number">02</div><h3>Choose</h3><p>Browse weekly stock, check availability, and reserve your items.</p></div>
          <div class="step-card"><div class="step-number">03</div><h3>Pickup</h3><p>Collect your pre-order at the market stall and pay the farmer directly.</p></div>
        </div>
      </div>
    </section>

    <section class="promo-banner-section">
      <i class="bi bi-flower1 promo-bg-pattern" aria-hidden="true"></i>
      <div class="container promo-flex">
        <div class="promo-content">
          <h2>Fresh Choices. Local Farmers.</h2>
          <p>Reserve weekly seasonal produce before market day and avoid sold-out stalls.</p>
          <a routerLink="/markets" class="btn btn-white">Explore Markets Nearby</a>
        </div>
        <div class="promo-img-box d-none d-md-block">
          <img src="https://images.unsplash.com/photo-1615485290382-441e4d049cb5?auto=format&fit=crop&w=600&q=80" alt="Fresh vegetable basket" width="600" height="600" loading="lazy" (error)="$any($event.target).style.display='none'">
        </div>
      </div>
    </section>

    <section class="cta-section" id="contact">
      <div class="container cta-box">
        <h2>Ready to Discover Your Local Market?</h2>
        <p class="reveal" style="--d: 90ms">Explore fresh seasonal produce, support local farming families, and reserve your next market pickup effortlessly — or list your own harvest as a farmer.</p>
        <div class="cta-btns reveal" style="--d: 180ms">
          <a routerLink="/register" class="btn btn-ml">Sign Up as a Customer</a>
          <a routerLink="/register" [queryParams]="{ role: 'farmer' }" class="btn btn-outline-ml">Register as a Farmer</a>
        </div>
      </div>
    </section>
  `,
})
export class HomeComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  readonly auth = inject(AuthService);

  data = signal<HomeData | null>(null);
  favs = signal<Set<number>>(new Set());

  money = money;
  img = img;
  dayName = dayName;
  date = date;
  time = formatTime;
  onImageError = imageError;
  onImageLoad = imageLoaded;
  limit = limit;
  openDaysLabel = openDaysLabel;

  /** Mirror of $openDays = $market->openDays(); used for the card meta lines. */
  openDays(market: HomeMarket): HomeMarketSchedule[] {
    return (market.schedules ?? [])
      .filter((s) => !s.is_closed)
      .sort((a, b) => Number(a.day_of_week) - Number(b.day_of_week));
  }

  firstOpen(market: HomeMarket): HomeMarketSchedule | null {
    return this.openDays(market)[0] ?? null;
  }

  openCount(market: HomeMarket): number {
    return this.openDays(market).length;
  }

  openNames(market: HomeMarket): string {
    return this.openDays(market).map((s) => dayName(s.day_of_week)).join(' & ');
  }

  ngOnInit(): void {
    this.api.get<HomeData>('/home').then((data) => {
      this.data.set(data);
      this.favs.set(new Set(data.favoriteProductIds ?? []));
    }).catch((error: unknown) => this.toast.fromValidation(error));
  }

  isFav(stock: HomeStock): boolean {
    return this.favs().has(stock.product.id);
  }

  async toggleFav(stock: HomeStock): Promise<void> {
    try {
      const response = await this.api.form<{ state: string }>('/customer/favorites/product', { product_id: stock.product.id });
      this.favs.update((favorites) => {
        const next = new Set(favorites);
        response.state === 'added' ? next.add(stock.product.id) : next.delete(stock.product.id);
        return next;
      });
    } catch (error) {
      this.toast.fromValidation(error);
    }
  }

  async addToCart(stock: HomeStock): Promise<void> {
    try {
      await this.api.form('/customer/cart/add', { stock_id: stock.id, quantity: 1 });
      this.toast.success(stock.product.name + ' added to cart.');
      void this.auth.load();
    } catch (error) {
      this.toast.fromValidation(error);
    }
  }
}

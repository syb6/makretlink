import { ChangeDetectionStrategy, Component, ElementRef, OnDestroy, OnInit, inject, signal, viewChild } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ApiService } from '../core/api.service';
import { AuthService } from '../core/auth.service';
import { ToastService } from '../core/toast.service';
import { dayName, formatTime, imageError, img, money, openDaysLabel } from '../core/utils';
import { LoadingStateComponent } from '../layout/loading-state.component';

declare const L: any;

/** Mirror of markets/show.blade.php — breadcrumb, favorite toggle, weekly
 *  schedule cards, farmer stalls, single-marker map, featured products. */
@Component({
  selector: 'app-market-detail',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, LoadingStateComponent],
  template: `
    @if (d(); as x) {
      <section class="py-4 py-md-5">
        <div class="container">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
              <li class="breadcrumb-item"><a routerLink="/markets">Markets</a></li>
              <li class="breadcrumb-item active">{{ x.market.name }}</li>
            </ol>
          </nav>

          <div class="row g-4">
            <div class="col-lg-7">
              <span class="badge-sub">LOCAL MARKET</span>
              <h1 class="section-title mb-2 d-flex align-items-center flex-wrap gap-2">
                {{ x.market.name }}
                @if (auth.isCustomer()) {
                  <button type="button" class="btn btn-sm {{ isFav ? 'btn-ml' : 'btn-outline-ml' }}" (click)="toggleFav()"
                          [attr.aria-pressed]="isFav">
                    <i class="bi {{ isFav ? 'bi-heart-fill' : 'bi-heart' }} me-1"></i>
                    {{ isFav ? 'Saved' : 'Save' }}
                  </button>
                }
              </h1>
              <p class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ x.market.address }}</p>
              @if (x.market.description) { <p>{{ x.market.description }}</p> }

              <h5 class="fw-bold mt-4 mb-3">Weekly schedule</h5>
              <div class="row g-2">
                @for (s of sortedSchedules(x.market.schedules); track s.id) {
                  <div class="col-6 col-md-4">
                    <div class="card-ml p-2 text-center {{ s.is_closed ? 'opacity-50' : '' }}">
                      <div class="fw-semibold small">{{ dayName(s.day_of_week) }}</div>
                      <div class="small text-muted">
                        {{ s.is_closed ? 'Closed' : time12(s.opening_time) + ' – ' + time12(s.closing_time) }}
                      </div>
                    </div>
                  </div>
                }
              </div>

              <h5 class="fw-bold mt-5 mb-3">Farmers at this market ({{ x.stalls?.length ?? 0 }})</h5>
              <div class="row g-3">
                @for (s of x.stalls ?? []; track s.id) {
                  <div class="col-md-6">
                    <div class="card-ml p-3 h-100">
                      <div class="d-flex justify-content-between mb-1 gap-2">
                        <a [routerLink]="['/farmers', s.farmer?.id]" class="fw-semibold text-decoration-none">{{ s.stall_name ?? s.farmer?.business_name }}</a>
                        <span class="status-pill status-{{ s.status }}">{{ s.status ? s.status.charAt(0).toUpperCase() + s.status.slice(1).replace('_', ' ') : 'Active' }}</span>
                      </div>
                      <div class="small text-muted mb-2">
                        <i class="bi bi-person me-1"></i>{{ s.contact_person ?? s.farmer?.contact_person }}
                        @if (s.stall_location) {
                          · <i class="bi bi-pin-map me-1"></i>{{ s.stall_location }}
                        }
                      </div>
                      <div class="small">
                        @for (fs of s.schedules ?? []; track fs.id) {
                          <span class="badge badge-soft me-1 mb-1">{{ dayName(fs.day_of_week) }} {{ shortTime(fs.start_time) }}–{{ shortTime(fs.end_time) }}</span>
                        }
                      </div>
                    </div>
                  </div>
                } @empty {
                  <div class="col-12"><div class="ml-note"><i class="bi bi-info-circle"></i>No farmers registered at this market yet.</div></div>
                }
              </div>
            </div>

            <div class="col-lg-5">
              <div id="map" #mapEl></div>
              <div class="card-ml p-4 mt-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-star me-2" style="color: var(--primary-dark)"></i>Featured products here</h6>
                <div class="d-grid gap-2">
                  @for (p of x.featuredProducts ?? []; track p.id) {
                    <a [routerLink]="['/products', p.id]" class="d-flex justify-content-between align-items-center text-decoration-none border-bottom pb-2">
                      <span>{{ p.name }} <small class="text-muted">· {{ p.farmer?.business_name }}</small></span>
                      <span class="product-price">{{ money(p.price) }}</span>
                    </a>
                  } @empty {
                    <span class="text-muted small">Nothing in stock right now.</span>
                  }
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    } @else {
      @if (loaded()) {
        <div class="alert alert-warning m-4" role="alert">This market could not be loaded. Please try again.</div>
      } @else {
        <app-loading-state label="Loading market…" />
      }
    }
  `,
})
export class MarketDetailComponent implements OnInit, OnDestroy {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  readonly auth = inject(AuthService);
  private route = inject(ActivatedRoute);

  d = signal<any>(null);
  loaded = signal(false);
  isFav = false;

  dayName = dayName; img = img; money = money; onImageError = imageError;

  private mapObj: any = null;
  private mapHost = viewChild<ElementRef<HTMLDivElement>>('mapEl');
  /** All leaflet pieces are keyed off the data, not the template. */
  private mapFor: any = null;

  ngOnInit(): void {
    void this.load();
  }

  ngOnDestroy(): void {
    if (this.mapObj?.remove) this.mapObj.remove();
  }

  time12(value: string | null | undefined): string {
    const m = (value ?? '').match(/(?:T|^)(\d{1,2}):(\d{2})/);
    if (!m) return value ?? '';
    let hour = Number(m[1]);
    const mm = m[2];
    const suffix = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12 === 0 ? 12 : hour % 12;
    return `${hour}:${mm} ${suffix}`;
  }

  shortTime(value: string | null | undefined): string {
    const m = (value ?? '').match(/(?:T|^)(\d{1,2}):(\d{2})/);
    if (!m) return value ?? '';
    let hour = Number(m[1]);
    const suffix = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12 === 0 ? 12 : hour % 12;
    return `${hour}${suffix}`;
  }

  sortedSchedules(list: any[] | undefined): any[] {
    return (list ?? []).slice().sort((a, b) => Number(a.day_of_week) - Number(b.day_of_week));
  }

  private async load(): Promise<void> {
    try {
      const x = await this.api.get<any>('/markets/' + this.route.snapshot.paramMap.get('id'));
      this.d.set(x);
      this.isFav = Boolean(x.isFavorite);
      void this.initMap(x);
    } catch {
      this.toast.fromValidation(new Error('This market could not be loaded.'));
    } finally {
      this.loaded.set(true);
    }
  }

  private async initMap(x: any): Promise<void> {
    const el: HTMLElement | undefined = this.mapHost()?.nativeElement;
    if (!el) return;
    const leaflet = await this.ensureLeaflet();
    if (!leaflet) return;
    const lat = Number(x.market.latitude ?? 24.8607) || 24.8607;
    const lng = Number(x.market.longitude ?? 67.0011) || 67.0011;
    this.mapObj = L.map(el).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(this.mapObj);
    L.marker([lat, lng]).addTo(this.mapObj)
      .bindPopup('<strong>' + x.market.name + '</strong><br>' + x.market.address)
      .openPopup();
  }

  private leafletPromise: Promise<boolean> | null = null;
  private ensureLeaflet(): Promise<boolean> {
    this.leafletPromise ??= new Promise((resolve) => {
      if (typeof (window as any).L !== 'undefined') return resolve(true);
      const css = document.createElement('link');
      css.rel = 'stylesheet';
      css.href = '/vendor/leaflet/leaflet.css';
      document.head.appendChild(css);
      const script = document.createElement('script');
      script.src = '/vendor/leaflet/leaflet.js';
      script.onload = () => resolve(true);
      script.onerror = () => {
        const cdnCss = document.createElement('link');
        cdnCss.rel = 'stylesheet';
        cdnCss.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(cdnCss);
        const cdn = document.createElement('script');
        cdn.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        cdn.onload = () => resolve(true);
        cdn.onerror = () => resolve(false);
        document.body.appendChild(cdn);
      };
      document.body.appendChild(script);
    });
    return this.leafletPromise;
  }

  async toggleFav(): Promise<void> {
    try {
      const res = await this.api.form<{ state: string }>('/customer/favorites/market', { market_id: this.d().market.id });
      this.isFav = res.state === 'added';
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

import { ChangeDetectionStrategy, Component, ElementRef, NgZone, OnDestroy, OnInit, inject, signal, viewChild } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../core/api.service';
import { AuthService } from '../core/auth.service';
import { ToastService } from '../core/toast.service';
import { dayName, formatTime, imageError, img, openDaysLabel } from '../core/utils';

interface MarketSchedule { id: number; day_of_week: number; is_closed: boolean; opening_time: string; closing_time: string; }
interface MarketRow { id: number; name: string; address: string; image?: string | null; status?: string; farmers_count?: number; schedules?: MarketSchedule[]; }
interface NearbyMarket { name: string; address: string; url: string; farmers_count: number; distance_km: string | number; }

/** Leaflet typed loosely — the global is loaded on demand like the Blade page does. */
declare const L: any;

@Component({
  selector: 'app-markets',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, FormsModule],
  template: `
    <section class="page-banner anim-up">
      <i class="bi bi-geo-alt-fill page-banner-icon" aria-hidden="true"></i>
      <div class="container">
        <span class="badge-sub">COMMUNITY HUBS</span>
        <h1 class="section-title">Farmers Markets</h1>
        <p class="section-subtitle mb-0">Find a market near you and see who is trading there.</p>
      </div>
    </section>
    <section class="py-4 py-md-5">
      <div class="container">
        <div class="row g-4">
          <div class="col-lg-4">
            <form class="card card-ml p-3 mb-4" (ngSubmit)="applyDay()">
              <label class="form-label fw-semibold small">Filter by operating day</label>
              <select name="day" class="form-select mb-3" [(ngModel)]="day">
                <option value="">Any day</option>
                @for (label of days(); track $index; let i = $index) { <option [value]="i">{{ label }}</option> }
              </select>
              <div class="d-flex gap-2">
                <button class="btn btn-ml btn-sm flex-grow-1">Apply</button>
                <a routerLink="/markets" class="btn btn-outline-ml btn-sm">Reset</a>
              </div>
            </form>

            <div class="card card-ml p-3 mb-4">
              <label class="form-label fw-semibold small"><i class="bi bi-geo-alt-fill me-1" style="color: var(--primary-dark)"></i>Markets near you</label>
              <p class="small text-muted mb-3">Use your device location to find active markets within 10 km.</p>
              <button id="findNearbyBtn" type="button" class="btn btn-ml w-100" (click)="findNearby()">
                <i class="bi bi-crosshair2 me-1" [class.spin-icon]="nearbyBusy()"></i>
                @if (nearbyBusy()) { Locating… } @else { Find Markets Near Me }
              </button>
              @if (nearbyStatus(); as status) {
                <div class="small mt-2" [class.text-danger]="status.type === 'error'" [class.text-muted]="status.type !== 'error'"
                     role="status" aria-live="polite" [innerHTML]="status.html"></div>
              }
            </div>

            <div id="map" class="mb-2" #mapEl></div>
            <p class="small text-muted"><i class="bi bi-info-circle me-1"></i>Click a marker for details &amp; directions (OpenStreetMap).</p>

            @if (nearby(); as nearbyData) {
              <div id="nearbyResults" class="d-block mt-3 anim-up">
                <div class="card-ml p-3 mb-3">
                  <div class="fw-semibold small mb-1"><i class="bi bi-geo me-1" style="color: var(--primary-dark)"></i>Your location</div>
                  <div class="small text-muted" style="word-break: break-word">{{ nearbyData.address }}</div>
                </div>
                <h6 class="fw-semibold small text-uppercase text-muted mb-2">{{ nearbyData.count }}</h6>
                <div class="d-flex flex-column gap-2">
                  @for (m of nearbyData.markets; track m.name) {
                    <a [href]="m.url" class="card-ml p-3 d-flex justify-content-between align-items-center text-decoration-none nearby-market-item">
                      <div class="me-2">
                        <div class="fw-semibold small">{{ m.name }}</div>
                        <div class="small text-muted">{{ m.address }}</div>
                        <div class="small text-muted"><i class="bi bi-people me-1"></i>{{ m.farmers_count }} farmer{{ m.farmers_count === 1 ? '' : 's' }}</div>
                      </div>
                      <span class="badge bg-ml-green-light text-ml-green fw-semibold flex-shrink-0">{{ m.distance_km }} km</span>
                    </a>
                  } @empty {
                    <div class="ml-note mb-0"><i class="bi bi-compass me-1"></i>No markets found within 10 km. Try widening the map instead.</div>
                  }
                </div>
              </div>
            }
          </div>

          <div class="col-lg-8">
            <div class="row g-4">
              @for (m of list(); track m.id; let index = $index) {
                <div class="col-md-6">
                  <div class="market-card h-100 position-relative">
                    <div class="market-img-box">
                      @if (auth.isCustomer()) {
                        <button type="button" class="fav-btn" (click)="toggleFav(m)"
                                [attr.title]="favs().has(m.id) ? 'Remove from favorites' : 'Save to favorites'"
                                [attr.aria-label]="'Toggle favorite market'">
                          <i class="bi" [class.bi-heart-fill]="favs().has(m.id)" [class.bi-heart]="!favs().has(m.id)"></i>
                        </button>
                      }
                      <span class="market-status-tag {{ m.status === 'active' ? '' : 'inactive' }}">{{ openDaysLabel(m.schedules) }}</span>
                      <img [src]="img('market', m.image)" (error)="onImageError($event, 'market')"
                           [alt]="m.name" width="600" height="400" loading="lazy">
                    </div>
                    <div class="market-details">
                      <h3 class="market-title">{{ m.name }}</h3>
                      <div class="market-meta">
                        <span><i class="bi bi-geo-alt-fill"></i>{{ m.address }}</span>
                      </div>
                      <div class="mb-3">
                        @if (openDays(m).length > 1) {
                          <p class="multi-market-note mb-2"><i class="bi bi-calendar-week me-1"></i>Trades {{ openDays(m).length }} days weekly</p>
                        }
                        @for (s of m.schedules ?? []; track s.id) {
                          <span class="badge badge-soft me-1 mb-1 {{ s.is_closed ? 'opacity-50 text-decoration-line-through' : '' }}">
                            {{ dayName(s.day_of_week) }}
                            @if (!s.is_closed) { {{ shortTime(s.opening_time) }}–{{ shortTime(s.closing_time) }} }
                          </span>
                        }
                      </div>
                      <div class="market-bottom">
                        <span class="vendor-count"><i class="bi bi-people me-1"></i>{{ m.farmers_count ?? 0 }} farmer{{ (m.farmers_count ?? 0) === 1 ? '' : 's' }}</span>
                        <a [routerLink]="['/markets', m.id]" class="btn-explore-market">View Market</a>
                      </div>
                    </div>
                  </div>
                </div>
              } @empty {
                <div class="col-12">
                  <div class="ml-note"><i class="bi bi-info-circle"></i>No markets match that day. Try another filter.</div>
                </div>
              }
            </div>

            @if (lastPage() > 1) {
              <nav class="mt-4" aria-label="Market pages">
                <ul class="pagination justify-content-center">
                  <li class="page-item" [class.disabled]="page() <= 1"><button class="page-link" type="button" (click)="go(page() - 1)" aria-label="Previous page">‹</button></li>
                  @for (p of pages(); track p) {
                    <li class="page-item" [class.active]="p === page()"><button class="page-link" type="button" (click)="go(p)">{{ p }}</button></li>
                  }
                  <li class="page-item" [class.disabled]="page() >= lastPage()"><button class="page-link" type="button" (click)="go(page() + 1)" aria-label="Next page">›</button></li>
                </ul>
              </nav>
            }
          </div>
        </div>
      </div>
    </section>
  `,
})
export class MarketsComponent implements OnInit, OnDestroy {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  private route = inject(ActivatedRoute);
  private zone = inject(NgZone);
  readonly auth = inject(AuthService);

  list = signal<MarketRow[]>([]);
  days = signal<string[]>([]);
  day = '';
  favs = signal<Set<number>>(new Set());
  page = signal(1);
  lastPage = signal(1);

  nearbyBusy = signal(false);
  nearbyStatus = signal<{ type: 'error' | 'info'; html: string } | null>(null);
  nearby = signal<{ address: string; count: string; markets: NearbyMarket[] } | null>(null);

  dayName = dayName;
  time = formatTime;
  img = img;
  onImageError = imageError;
  openDaysLabel = openDaysLabel;

  private mapObj: any = null;

  private mapHost = viewChild<ElementRef<HTMLDivElement>>('mapEl');

  ngOnInit(): void {
    this.day = this.route.snapshot.queryParamMap.get('day') ?? '';
    void this.load();
  }

  shortTime(value: string | null | undefined): string {
    // Blade formats opening/closing as gA: 9AM / 5PM
    const m = (value ?? '').match(/(?:T|^)(\d{1,2}):(\d{2})/);
    if (!m) return value ?? '';
    let hour = Number(m[1]);
    const suffix = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12 === 0 ? 12 : hour % 12;
    return `${hour}${suffix}`;
  }

  openDays(m: MarketRow): MarketSchedule[] {
    return (m.schedules ?? []).filter((s) => !s.is_closed).sort((a, b) => Number(a.day_of_week) - Number(b.day_of_week));
  }

  pages(): number[] {
    return Array.from({ length: this.lastPage() }, (_, i) => i + 1);
  }

  applyDay(): void {
    this.page.set(1);
    void this.load();
  }

  go(p: number): void {
    if (p < 1 || p > this.lastPage()) return;
    this.page.set(p);
    void this.load();
  }

  async load(): Promise<void> {
    const qs = new URLSearchParams();
    if (this.day !== '') qs.set('day', this.day);
    qs.set('page', String(this.page()));
    try {
      const d = await this.api.get<any>('/markets?' + qs.toString());
      this.list.set(d.data);
      this.days.set(d.days ? Object.values(d.days) : []);
      this.favs.set(new Set(d.favoriteMarketIds ?? []));
      this.page.set(d.page);
      this.lastPage.set(d.last_page);
    } catch (error) {
      this.toast.fromValidation(error);
    }
    void this.initMap();
  }

  ngOnDestroy(): void {
    if (this.mapObj?.remove) this.mapObj.remove();
  }

  /** Port of the Blade page's leaflet setup: marker popups + fitBounds. */
  private async initMap(): Promise<void> {
    const el: HTMLElement | undefined = this.mapHost()?.nativeElement;
    if (!el) return;
    const leaflet = await this.ensureLeaflet();
    if (!leaflet) return;
    if (!this.mapObj) {
      this.mapObj = L.map(el).setView([24.8607, 67.0011], 11); // Karachi
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
      }).addTo(this.mapObj);
    }

    try {
      const geo = await this.api.get<any>('/markets-map');
      const layer = L.geoJSON(geo, {
        onEachFeature: (feature: any, marker: any) => {
          const p = feature.properties ?? {};
          marker.bindPopup(
            '<strong>' + p.name + '</strong><br>' + p.address +
            '<br>' + p.farmers + ' farmer(s)' +
            '<br><a href="' + p.url + '">View market</a>' +
            '<br><a href="https://www.google.com/maps/dir/?api=1&destination=' + feature.geometry.coordinates[1] + ',' + feature.geometry.coordinates[0] + '" target="_blank" rel="noopener">Get directions ↗</a>',
          );
        },
      }).addTo(this.mapObj);
      try { this.mapObj.fitBounds(layer.getBounds().pad(0.25)); } catch { /* ignore */ }      } catch { /* map data failure must not break the page */ }
  }

  /** Load /vendor/leaflet with CDN fallback, exactly like the Blade pushes. */
  private leafletPromise: Promise<boolean> | null = null;
  private ensureLeaflet(): Promise<boolean> {
    this.leafletPromise ??= new Promise((resolve) => {
      const load = (jsSrc: string, cssHref: string, thenCdn?: () => void) => {
        // CSS head injection
        if (!document.querySelector('link[data-leaflet]')) {
          const css = document.createElement('link');
          css.rel = 'stylesheet';
          css.href = cssHref;
          css.setAttribute('data-leaflet', '1');
          document.head.appendChild(css);
        }
        const script = document.createElement('script');
        script.src = jsSrc;
        script.onload = () => resolve(true);
        script.onerror = () => { if (thenCdn) thenCdn(); else resolve(false); };
        document.body.appendChild(script);
      };
      if (typeof (window as any).L !== 'undefined') return resolve(true);
      load('/vendor/leaflet/leaflet.js', '/vendor/leaflet/leaflet.css', () => {
        load('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
      });
    });
    return this.leafletPromise;
  }

  async findNearby(): Promise<void> {
    if (this.nearbyBusy()) return;
    if (!navigator.geolocation) {
      this.nearbyStatus.set({ type: 'error', html: '<i class="bi bi-x-circle me-1"></i>Geolocation is not supported by this browser.' });
      return;
    }    this.nearbyBusy.set(true);
    this.nearbyStatus.set({ type: 'info', html: '<i class="bi bi-hourglass-split me-1"></i>Waiting for your location...'});

    navigator.geolocation.getCurrentPosition(async (pos) => {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      this.nearbyStatus.set({ type: 'info', html: '<i class="bi bi-hourglass-split me-1"></i>Finding markets near you...' });

      try {
        const data = await this.api.get<any>(
          '/markets/nearby?latitude=' + encodeURIComponent(String(lat)) + '&longitude=' + encodeURIComponent(String(lng)) + '&radius=10',
        );

        this.nearbyStatus.set(null);
        const loc = data.user_location ?? {};
        const address = loc.address || ('Coordinates: ' + Number(loc.latitude ?? lat).toFixed(5) + ', ' + Number(loc.longitude ?? lng).toFixed(5));
        const markets: NearbyMarket[] = data.markets ?? [];
        this.nearby.set({
          address,
          count: markets.length
            ? markets.length + ' market' + (markets.length === 1 ? '' : 's') + ' within ' + data.radius_km + ' km'
            : '',
          markets,
        });

        // Mirror the Blade behavior: fly the map to the user + drop a marker.
        if (this.mapObj) {
          try {
            this.mapObj.flyTo([lat, lng], 13, { duration: 0.8 });
            L.circleMarker([lat, lng], {
              radius: 8, color: '#889721', weight: 2, fillColor: '#a2b231', fillOpacity: 0.85,
              className: 'market-you-marker',
            }).addTo(this.mapObj).bindPopup('You are here');
          } catch { /* ignore */ }
        }
      } catch {
        this.nearbyStatus.set({ type: 'error', html: '<i class="bi bi-x-circle me-1"></i>Could not load nearby markets. Please try again.' });
      } finally {
        this.nearbyBusy.set(false);
      }
    }, (err) => {
      this.nearbyBusy.set(false);
      const messages: Record<number, string> = {
        1: 'Location permission denied. Allow location access and try again.',
        2: 'Location unavailable right now. Please try again.',
        3: 'Location request timed out. Please try again.',
      };
      this.nearbyStatus.set({ type: 'error', html: '<i class="bi bi-x-circle me-1"></i>' + (messages[err.code] ?? 'Could not get your location.') });
    }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 });
  }

  async toggleFav(m: MarketRow): Promise<void> {
    try {
      const res = await this.api.form<{ state: string }>('/customer/favorites/market', { market_id: m.id });
      this.favs.update((f) => {
        const n = new Set(f);
        res.state === 'added' ? n.add(m.id) : n.delete(m.id);
        return n;
      });
    } catch (error) {
      this.toast.fromValidation(error);
    }
  }
}

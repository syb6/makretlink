import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ApiService } from '../core/api.service';
import { AuthService } from '../core/auth.service';
import { ToastService } from '../core/toast.service';
import { dayName, formatTime, imageError, img } from '../core/utils';

@Component({
  selector: 'app-markets',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink],
  template: `
    <section class="page-banner">
      <div class="container">
        <span class="badge-sub">COMMUNITY HUBS</span>
        <h1 class="section-title">Explore <span class="accent">Local Markets</span></h1>
        <p class="section-subtitle">Find a nearby market, check its weekly schedule, and meet local growers.</p>
        <i class="bi bi-shop page-banner-icon" aria-hidden="true"></i>
      </div>
    </section>

    <div class="container py-4 py-lg-5">
      <div class="d-flex flex-wrap align-items-center gap-2 mb-4" role="group" aria-label="Filter markets by day">
        <button type="button" class="btn btn-sm" [class.btn-ml]="day() === ''" [class.btn-outline-ml]="day() !== ''" (click)="setDay('')">All days</button>
        @for (d of days(); track $index) {
          <button type="button" class="btn btn-sm" [class.btn-ml]="day() === dayKey($index)" [class.btn-outline-ml]="day() !== dayKey($index)" (click)="setDay(dayKey($index))">{{ d }}</button>
        }
      </div>

      <div class="markets-grid">
        @for (m of list(); track m.id; let index = $index) {
          <article class="market-card">
            <div class="market-img-box">
              <span class="market-status-tag">Local market</span>
              <img [src]="img('market', m.image)" (error)="onImageError($event, 'market')"
                   [alt]="m.name" width="600" height="400"
                   [attr.loading]="index < 3 ? 'eager' : 'lazy'"
                   [attr.fetchpriority]="index === 0 ? 'high' : 'auto'">
              @if (auth.isCustomer()) {
                <button class="fav-btn" type="button" (click)="toggleFav(m)"
                        [attr.aria-label]="favs().has(m.id) ? 'Remove market from favorites' : 'Save market to favorites'">
                  <i class="bi" [class.bi-heart-fill]="favs().has(m.id)" [class.bi-heart]="!favs().has(m.id)"></i>
                </button>
              }
            </div>
            <div class="market-details">
              <h2 class="market-title">{{ m.name }}</h2>
              <div class="market-meta">
                <span><i class="bi bi-geo-alt-fill"></i>{{ m.address }}</span>
                @for (s of m.schedules ?? []; track s.id) {
                  @if (!s.is_closed) {
                    <span [title]="time(s.opening_time) + '–' + time(s.closing_time)">
                    <i class="bi bi-clock-fill"></i>{{ dayName(s.day_of_week) }} · {{ time(s.opening_time) }}–{{ time(s.closing_time) }}
                    </span>
                  }
                }
              </div>
              <div class="market-bottom">
                <span class="vendor-count"><i class="bi bi-people-fill me-1"></i>{{ m.farmers_count ?? 0 }} local farmers</span>
                <a [routerLink]="['/markets', m.id]" class="btn-explore-market">View Market</a>
              </div>
            </div>
          </article>
        } @empty {
          <div class="ml-note"><i class="bi bi-info-circle"></i>No open markets for this day.</div>
        }
      </div>

      @if (lastPage() > 1) {
        <nav class="mt-4" aria-label="Market pages">
          <ul class="pagination justify-content-center">
            <li class="page-item" [class.disabled]="page() <= 1">
              <button class="page-link" type="button" (click)="go(page() - 1)" aria-label="Previous page">‹</button>
            </li>
            @for (p of pages(); track p) {
              <li class="page-item" [class.active]="p === page()">
                <button class="page-link" type="button" (click)="go(p)" [attr.aria-current]="p === page() ? 'page' : null">{{ p }}</button>
              </li>
            }
            <li class="page-item" [class.disabled]="page() >= lastPage()">
              <button class="page-link" type="button" (click)="go(page() + 1)" aria-label="Next page">›</button>
            </li>
          </ul>
        </nav>
      }
    </div>
  `,
})
export class MarketsComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  private route = inject(ActivatedRoute);
  readonly auth = inject(AuthService);

  list = signal<any[]>([]);
  days = signal<string[]>([]);
  day = signal('');
  favs = signal<Set<number>>(new Set());
  page = signal(1);
  lastPage = signal(1);

  dayName = dayName;
  time = formatTime;
  img = img;
  onImageError = imageError;

  dayKey(index: number): string {
    return String(index);
  }

  ngOnInit(): void {
    this.day.set(this.route.snapshot.queryParamMap.get('day') ?? '');
    void this.load();
  }

  pages(): number[] {
    return Array.from({ length: this.lastPage() }, (_, i) => i + 1);
  }

  setDay(day: string): void {
    this.day.set(day);
    this.page.set(1);
    void this.load();
  }

  go(page: number): void {
    if (page < 1 || page > this.lastPage()) return;
    this.page.set(page);
    void this.load();
  }

  async load(): Promise<void> {
    const qs = new URLSearchParams();
    if (this.day() !== '') qs.set('day', this.day());
    qs.set('page', String(this.page()));
    try {
      const d = await this.api.get<any>('/markets?' + qs.toString());
      this.list.set(d.data);
      this.days.set(d.days);
      this.favs.set(new Set(d.favoriteMarketIds ?? []));
      this.page.set(d.page);
      this.lastPage.set(d.last_page);
    } catch (error) {
      this.toast.fromValidation(error);
    }
  }

  async toggleFav(m: any): Promise<void> {
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

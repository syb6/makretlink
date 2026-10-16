import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../core/api.service';
import { AuthService } from '../core/auth.service';
import { ToastService } from '../core/toast.service';
import { dayName, limit } from '../core/utils';
import { LoadingStateComponent } from '../layout/loading-state.component';

@Component({
  selector: 'app-farmers',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, FormsModule, LoadingStateComponent],
  template: `
    <section class="page-banner anim-up">
      <i class="bi bi-flower2 page-banner-icon" aria-hidden="true"></i>
      <div class="container">
        <span class="badge-sub">MEET THE GROWERS</span>
        <h1 class="section-title mb-1">Our Farmers</h1>
        <p class="section-subtitle mb-0">Meet the people growing your food.</p>
      </div>
    </section>

    <div class="container py-4 py-lg-5">
    <form class="d-flex gap-2 flex-wrap justify-content-center mb-4" (ngSubmit)="page.set(1); load()">
      <input type="text" name="q" class="form-control" style="max-width:220px" placeholder="Search farmers..." [(ngModel)]="q">
      <select name="market" class="form-select" style="max-width:200px" [(ngModel)]="market">
        <option value="">All markets</option>
        @for (m of markets(); track m.id) { <option [value]="m.id">{{ m.name }}</option> }
      </select>
      <button class="btn btn-ml">Filter</button>
    </form>

    <div class="row g-4">
      @if (loading() && list().length === 0) {
        <div class="col-12"><app-loading-state label="Loading local farmers…" /></div>
      } @else {
        @for (f of list(); track f.id) {
        <div class="col-sm-6 col-lg-4">
          <div class="card-ml h-100 p-4 d-flex flex-column position-relative">
            @if (auth.isCustomer()) {
              <button type="button" class="fav-btn" (click)="toggleFav(f)"
                      [attr.title]="favs().has(f.id) ? 'Remove from favorites' : 'Save to favorites'"
                      aria-label="Toggle favorite farmer">
                <i class="bi" [class.bi-heart-fill]="favs().has(f.id)" [class.bi-heart]="!favs().has(f.id)"></i>
              </button>
            }
            <div class="d-flex align-items-center gap-3 mb-3">
              <span class="brand-leaf">{{ initial(f.business_name) }}</span>
              <div>
                <h5 class="fw-bold mb-0">{{ f.business_name }}</h5>
                <small class="text-muted"><i class="bi bi-person me-1"></i>{{ f.contact_person }}</small>
              </div>
            </div>
            <p class="small text-muted mb-3">{{ limit(f.description || f.address, 110) }}</p>
            <div class="mb-3">
              @for (m of f.markets ?? []; track m.id) {
                <span class="badge badge-soft mb-1"><i class="bi bi-geo-alt me-1"></i>{{ m.market?.name }}</span>
              }
            </div>
            <div class="mt-auto">
              <a [routerLink]="['/farmers', f.id]" class="btn btn-outline-ml btn-sm w-100">View profile &amp; stock</a>
            </div>
          </div>
        </div>
        } @empty {
          <div class="col-12"><div class="ml-note"><i class="bi bi-info-circle"></i>No farmers found. Try different filters.</div></div>
        }
      }
    </div>

    @if (lastPage() > 1) {
      <nav class="mt-4"><ul class="pagination justify-content-center">
        <li class="page-item" [class.disabled]="page() <= 1"><button class="page-link" type="button" (click)="go(page() - 1)" aria-label="Previous page">‹</button></li>
        @for (p of pages(); track p) {
          <li class="page-item" [class.active]="p === page()"><button class="page-link" type="button" (click)="go(p)">{{ p }}</button></li>
        }
        <li class="page-item" [class.disabled]="page() >= lastPage()"><button class="page-link" type="button" (click)="go(page() + 1)" aria-label="Next page">›</button></li>
      </ul></nav>
    }
    </div>
  `,
})
export class FarmersComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  private route = inject(ActivatedRoute);
  readonly auth = inject(AuthService);

  q = '';
  market = '';
  list = signal<any[]>([]);
  markets = signal<any[]>([]);
  favs = signal<Set<number>>(new Set());
  page = signal(1);
  lastPage = signal(1);
  loading = signal(true);

  dayName = dayName;
  limit = limit;
  ngOnInit(): void {
    this.market = this.route.snapshot.queryParamMap.get('market') ?? '';
    void this.load();
  }

  initial(name: string | null | undefined): string {
    return (name ?? '').trim().charAt(0).toUpperCase();
  }

  go(p: number): void {
    if (p < 1 || p > this.lastPage()) return;
    this.page.set(p);
    void this.load();
  }

  pages(): number[] { return Array.from({ length: this.lastPage() }, (_, i) => i + 1); }

  async load(): Promise<void> {
    this.loading.set(true);
    const qs = new URLSearchParams({ page: String(this.page()) });
    if (this.q) qs.set('q', this.q);
    if (this.market) qs.set('market', this.market);
    try {
      const d = await this.api.get<any>('/farmers?' + qs);
      this.list.set(d.data);
      this.markets.set(d.markets);
      this.favs.set(new Set(d.favoriteFarmerIds ?? []));
      this.page.set(d.page);
      this.lastPage.set(d.last_page);
    } catch (error) {
      this.toast.fromValidation(error);
    } finally {
      this.loading.set(false);
    }
  }

  async toggleFav(f: any): Promise<void> {
    try {
      const res = await this.api.form<{ state: string }>('/customer/favorites/farmer', { farmer_id: f.id });
      this.favs.update((s) => {
        const n = new Set(s);
        res.state === 'added' ? n.add(f.id) : n.delete(f.id);
        return n;
      });
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

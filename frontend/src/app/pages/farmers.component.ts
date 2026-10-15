import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../core/api.service';
import { AuthService } from '../core/auth.service';
import { ToastService } from '../core/toast.service';
import { dayName } from '../core/utils';
import { PersonAvatarComponent } from '../core/person-avatar.component';

@Component({
  selector: 'app-farmers',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, FormsModule, PersonAvatarComponent],
  template: `
    <section class="page-banner">
      <div class="container">
        <span class="badge-sub">MEET THE GROWERS</span>
        <h1 class="section-title">Local <span class="accent">Farmers</span></h1>
        <p class="section-subtitle">Discover the people and small businesses bringing fresh goods to your market.</p>
        <i class="bi bi-people page-banner-icon" aria-hidden="true"></i>
      </div>
    </section>

    <div class="container py-4 py-lg-5">
    <form class="card filter-card mb-4" autocomplete="off">
      <div class="card-body row g-2 align-items-end">
        <div class="col-md-6">
          <label class="form-label small mb-1" for="farmer-search">Search farmers</label>
          <input id="farmer-search" class="form-control" name="q" [(ngModel)]="q" placeholder="Search by farm, person or name…" (input)="load()">
        </div>
        <div class="col-md-4">
          <label class="form-label small mb-1" for="farmer-market">Market</label>
          <select id="farmer-market" class="form-select" name="market" [(ngModel)]="market" (change)="load()">
            <option value="">All markets</option>
            @for (m of markets(); track m.id) { <option [value]="m.id">{{ m.name }}</option> }
          </select>
        </div>
      </div>
    </form>

    <div class="row g-4">
      @for (f of list(); track f.id) {
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <app-person-avatar [name]="f.name || f.contact_person" [src]="f.avatar || f.profile_image" class="avatar-lg rounded-circle me-3" />
                <div class="flex-grow-1">
                  <h6 class="mb-0 fw-bold"><a [routerLink]="['/farmers', f.id]" class="text-decoration-none text-reset">{{ f.business_name }}</a></h6>
                  <div class="small text-muted">{{ f.name }} @if (f.contact_person && f.contact_person !== f.name) { · {{ f.contact_person }} }</div>
                </div>
                @if (auth.isCustomer()) {
                  <button class="btn btn-link text-danger fs-5 p-0" (click)="toggleFav(f)"><i class="bi" [class.bi-heart-fill]="favs().has(f.id)" [class.bi-heart]="!favs().has(f.id)"></i></button>
                }
              </div>
              @if (f.description) { <p class="small text-muted mb-2">{{ f.description }}</p> }
              <div class="small">
                @for (m of (f.markets ?? []).slice(0, 3); track m.id) {
                  <div class="mb-1">
                    <i class="bi bi-shop me-1 text-success"></i>{{ m.market?.name }}
                    @for (s of m.schedules; track s.id) { <span class="badge bg-light text-dark me-1">{{ dayName(s.day_of_week) }}</span> }
                  </div>
                } @empty { <span class="text-muted">No stalls yet</span> }
              </div>
            </div>
          </div>
        </div>
      } @empty {
        <p class="text-muted">No farmers found.</p>
      }
    </div>

    @if (lastPage() > 1) {
      <nav class="mt-4"><ul class="pagination justify-content-center">
        @for (p of pages(); track p) {
          <li class="page-item" [class.active]="p === page()"><button class="page-link" (click)="page.set(p); load()">{{ p }}</button></li>
        }
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

  dayName = dayName;
  ngOnInit(): void {
    this.market = this.route.snapshot.queryParamMap.get('market') ?? '';
    void this.load();
  }

  pages(): number[] { return Array.from({ length: this.lastPage() }, (_, i) => i + 1); }

  async load(): Promise<void> {
    const qs = new URLSearchParams({ page: String(this.page()) });
    if (this.q) qs.set('q', this.q);
    if (this.market) qs.set('market', this.market);
    const d = await this.api.get<any>('/farmers?' + qs);
    this.list.set(d.data);
    this.markets.set(d.markets);
    this.favs.set(new Set(d.favoriteFarmerIds ?? []));
    this.page.set(d.page);
    this.lastPage.set(d.last_page);
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

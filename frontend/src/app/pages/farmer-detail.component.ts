import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from '../core/api.service';
import { AuthService } from '../core/auth.service';
import { ToastService } from '../core/toast.service';
import { dayName, formatTime, imageError, img, money } from '../core/utils';
import { PersonAvatarComponent } from '../core/person-avatar.component';
import { LoadingStateComponent } from '../layout/loading-state.component';

@Component({
  selector: 'app-farmer-detail',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [PersonAvatarComponent, LoadingStateComponent],
  template: `
    @if (d(); as x) {
      <div class="card detail-panel farmer-profile-panel mb-4">
        <div class="card-body d-lg-flex align-items-center gap-3">
          <app-person-avatar [name]="x.farmer.name || x.farmer.contact_person" [src]="x.farmer.avatar || x.farmer.profile_image" class="avatar-lg rounded-circle mb-3 mb-lg-0" />
          <div class="flex-grow-1">
            <h2 class="fw-bold mb-1">{{ x.farmer.business_name }}</h2>
            <div class="text-muted small mb-2">{{ x.farmer.name }} · {{ x.farmer.contact_person }}</div>
            @if (x.farmer.description) { <p class="mb-2">{{ x.farmer.description }}</p> }
            <div>
              @for (m of x.farmer.markets; track m.id) {
                <div class="small">
                  <i class="bi bi-shop me-1 text-success"></i>{{ m.market?.name }} — the {{ dayName(m.schedules?.[0]?.day_of_week) }} @if (m.schedules?.[0]) { {{ time(m.schedules[0].start_time) }}–{{ time(m.schedules[0].end_time) }} }
                </div>
              }
            </div>
          </div>
          @if (auth.isCustomer()) {
            <button class="btn btn-outline-danger align-self-start" (click)="toggleFav()">
              <i class="bi" [class.bi-heart-fill]="favs().has(x.farmer.id)" [class.bi-heart]="!favs().has(x.farmer.id)"></i>
              {{ favs().has(x.farmer.id) ? 'Favorite farmer' : 'Add to favorites' }}
            </button>
          }
        </div>
      </div>

      <h2 class="section-title mb-3">Buy this week's stock</h2>
      <div class="row g-4">
        @for (s of x.stocks; track s.id) {
          <div class="col-6 col-lg-3">
            <div class="card h-100 shadow-sm">
              <img [src]="img('product', s.product.image)" (error)="onImageError($event, 'product')" class="card-img-top card-img-cover" [alt]="s.product.name">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <h6 class="text-truncate">{{ s.product.name }}</h6>
                  @if (auth.isCustomer()) {
                    <button class="btn btn-link btn-sm p-0 text-danger fs-5" (click)="toggleProductFav(s)"><i class="bi" [class.bi-heart-fill]="pFavs().has(s.product.id)" [class.bi-heart]="!pFavs().has(s.product.id)"></i></button>
                  }
                </div>
                <div class="text-success fw-bold">{{ money(s.product.price) }} <span class="text-muted small fw-normal">/ {{ s.product.unit }}</span></div>
                <div class="small text-muted mb-2">{{ s.week_start }} · {{ s.farmerMarket?.market?.name }}</div>
                @if (auth.isCustomer()) {
                  <button class="btn btn-sm btn-ml w-100" (click)="add(s)">Add to cart</button>
                }
              </div>
            </div>
          </div>
        } @empty {
          <p class="text-muted">No sellable stock this week — check back next market day.</p>
        }
      </div>
    }
    @else if (!loaded()) {
      <app-loading-state label="Loading farmer profile…" />
    }
    @else {
      <div class="alert alert-warning" role="alert">This farmer profile could not be loaded. Please try again.</div>
    }
  `,
})
export class FarmerDetailComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  readonly auth = inject(AuthService);
  private route = inject(ActivatedRoute);

  d = signal<any>(null);
  loaded = signal(false);
  favs = signal<Set<number>>(new Set());
  pFavs = signal<Set<number>>(new Set());

  dayName = dayName; img = img; money = money; time = formatTime;
  onImageError = imageError;

  ngOnInit(): void {
    this.api.get<any>('/farmers/' + this.route.snapshot.paramMap.get('id')).then((x) => {
      this.d.set(x);
      this.favs.set(new Set(x.favoriteFarmerIds ?? []));
      this.pFavs.set(new Set(x.favoriteProductIds ?? []));
    }).catch((error: unknown) => this.toast.fromValidation(error))
      .finally(() => this.loaded.set(true));
  }

  async toggleFav(): Promise<void> {
    const id = this.d().farmer.id;
    try {
      const res = await this.api.form<{ state: string }>('/customer/favorites/farmer', { farmer_id: id });
      this.favs.update((s) => {
        const n = new Set(s);
        res.state === 'added' ? n.add(id) : n.delete(id);
        return n;
      });
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  async toggleProductFav(s: any): Promise<void> {
    try {
      const res = await this.api.form<{ state: string }>('/customer/favorites/product', { product_id: s.product.id });
      this.pFavs.update((st) => {
        const n = new Set(st);
        res.state === 'added' ? n.add(s.product.id) : n.delete(s.product.id);
        return n;
      });
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  async add(s: any): Promise<void> {
    try {
      await this.api.form('/customer/cart/add', { stock_id: s.id, quantity: 1 });
      this.toast.success(s.product.name + ' added to cart.');
      void this.auth.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

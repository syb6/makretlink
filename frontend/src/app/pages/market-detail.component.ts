import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ApiService } from '../core/api.service';
import { ToastService } from '../core/toast.service';
import { dayName, formatTime, imageError, img, money } from '../core/utils';

@Component({
  selector: 'app-market-detail',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink],
  template: `
    @if (d(); as x) {
      <div class="detail-panel">
      <div class="row g-4 mb-2">
        <div class="col-lg-6">
          <img [src]="img('market', x.market.image)" (error)="onImageError($event, 'market')" class="w-100 rounded-4 shadow-sm" style="max-height:320px;object-fit:cover" [alt]="x.market.name">
        </div>
        <div class="col-lg-6">
          <h2 class="fw-bold">{{ x.market.name }}</h2>
          <p class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ x.market.address }}</p>
          @if (x.market.description) { <p>{{ x.market.description }}</p> }
          <h6 class="fw-bold">Opening hours</h6>
          <ul class="list-unstyled small">
            @for (s of x.market.schedules; track s.id) {
              <li>
                <span class="badge" [class.bg-success]="!s.is_closed" [class.bg-secondary]="s.is_closed">{{ dayName(s.day_of_week) }}</span>
                @if (s.is_closed) { <span class="text-muted"> closed</span> }
                @else { <span>{{ time(s.opening_time) }} – {{ time(s.closing_time) }}</span> }
              </li>
            }
          </ul>
        </div>
      </div>
      </div>

      <h2 class="section-title mb-3">Stalls ({{ x.stalls.length }})</h2>
      <div class="row g-3 mb-4">
        @for (s of x.stalls; track s.id) {
          <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
              <div class="card-body">
                <h6 class="fw-bold mb-1">{{ s.stall_name || s.farmer?.business_name }}</h6>
                <div class="small text-muted mb-1">{{ s.farmer?.business_name }}</div>
                <div class="small mb-2">
                  @for (sc of s.schedules; track sc.id) {
                    <span class="badge bg-light text-dark me-1">{{ dayName(sc.day_of_week) }} {{ time(sc.start_time) }}–{{ time(sc.end_time) }}</span>
                  }
                </div>
                <a [routerLink]="['/farmers', s.farmer?.id]" class="btn btn-sm btn-outline-success">Visit farmer</a>
              </div>
            </div>
          </div>
        }
      </div>

      @if (x.featuredProducts.length) {
        <h2 class="section-title mb-3">What they have this week</h2>
        <div class="row g-3">
          @for (p of x.featuredProducts; track p.id) {
            <div class="col-6 col-lg-3">
              <a [routerLink]="['/products', p.id]" class="card text-decoration-none text-reset h-100 shadow-sm">
                <img [src]="img('product', p.image)" (error)="onImageError($event, 'product')" class="card-img-top card-img-cover" [alt]="p.name">
                <div class="card-body py-2">
                  <div class="small fw-semibold text-truncate">{{ p.name }}</div>
                  <div class="small text-success fw-bold">{{ money(p.price) }} / {{ p.unit }}</div>
                </div>
              </a>
            </div>
          }
        </div>
      }
    }
  `,
})
export class MarketDetailComponent implements OnInit {
  private api = inject(ApiService);
  private route = inject(ActivatedRoute);

  d = signal<any>(null);

  dayName = dayName; img = img; money = money; time = formatTime; onImageError = imageError;

  ngOnInit(): void {
    this.api.get<any>('/markets/' + this.route.snapshot.paramMap.get('id')).then((x) => this.d.set(x));
  }
}

import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { imageError, img, money } from '../../core/utils';
import { PersonAvatarComponent } from '../../core/person-avatar.component';

@Component({
  selector: 'app-favorites',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, PersonAvatarComponent],
  template: `
    <h2 class="section-title mb-3">Favorites</h2>

    <ul class="nav nav-tabs mb-3">
      <li class="nav-item"><button class="nav-link" [class.active]="tab() === 'products'" (click)="tab.set('products')">Products ({{ products().length }})</button></li>
      <li class="nav-item"><button class="nav-link" [class.active]="tab() === 'farmers'" (click)="tab.set('farmers')">Farmers ({{ farmers().length }})</button></li>
      <li class="nav-item"><button class="nav-link" [class.active]="tab() === 'markets'" (click)="tab.set('markets')">Markets ({{ markets().length }})</button></li>
    </ul>

    @switch (tab()) {
      @case ('products') {
        <div class="row g-4">
          @for (p of products(); track p.id) {
            <div class="col-6 col-lg-3">
              <div class="card h-100 shadow-sm">
                <img [src]="img('product', p.image)" (error)="onImageError($event, 'product')" class="card-img-top card-img-cover" [alt]="p.name">
                <div class="card-body">
                  <h6 class="text-truncate"><a [routerLink]="['/products', p.id]" class="text-decoration-none text-reset">{{ p.name }}</a></h6>
                  <div class="text-success fw-bold mb-2">{{ money(p.price) }} / {{ p.unit }}</div>
                  <button class="btn btn-sm btn-outline-danger w-100" (click)="unfav('product', p.id)">Remove</button>
                </div>
              </div>
            </div>
          } @empty { <p class="text-muted">Nothing favorited yet — tap the ♥ on any product.</p> }
        </div>
      }
      @case ('farmers') {
        <div class="row g-4">
          @for (f of farmers(); track f.id) {
            <div class="col-md-6 col-lg-4">
              <div class="card h-100 shadow-sm"><div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <app-person-avatar [name]="f.name || f.contact_person" [src]="f.avatar || f.profile_image" class="avatar-sm rounded-circle" />
                  <h6 class="fw-bold mb-0"><a [routerLink]="['/farmers', f.id]" class="text-decoration-none text-reset">{{ f.business_name }}</a></h6>
                </div>
                <div class="small text-muted mb-2">{{ f.name }}</div>
                <button class="btn btn-sm btn-outline-danger" (click)="unfav('farmer', f.id)">Remove</button>
              </div></div>
            </div>
          } @empty { <p class="text-muted">No favorite farmers yet.</p> }
        </div>
      }
      @case ('markets') {
        <div class="row g-4">
          @for (m of markets(); track m.id) {
            <div class="col-md-6 col-lg-4">
              <div class="card h-100 shadow-sm">
                <img [src]="img('market', m.image)" (error)="onImageError($event, 'market')" class="card-img-top card-img-cover" [alt]="m.name">
                <div class="card-body">
                  <h6 class="fw-bold"><a [routerLink]="['/markets', m.id]" class="text-decoration-none text-reset">{{ m.name }}</a></h6>
                  <div class="small text-muted mb-2">{{ m.address }}</div>
                  <button class="btn btn-sm btn-outline-danger" (click)="unfav('market', m.id)">Remove</button>
                </div>
              </div>
            </div>
          } @empty { <p class="text-muted">No favorite markets yet.</p> }
        </div>
      }
    }
  `,
})
export class FavoritesComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  tab = signal<'products' | 'farmers' | 'markets'>('products');
  products = signal<any[]>([]);
  farmers = signal<any[]>([]);
  markets = signal<any[]>([]);

  money = money; img = img; onImageError = imageError;

  ngOnInit(): void {
    void this.load();
  }

  async load(): Promise<void> {
    const d = await this.api.get<any>('/customer/favorites');
    this.products.set(d.products ?? []);
    this.farmers.set(d.farmers ?? []);
    this.markets.set(d.markets ?? []);
  }

  async unfav(type: string, id: number): Promise<void> {
    try {
      await this.api.form('/customer/favorites/' + type, { [type === 'product' ? 'product_id' : type + '_id']: id });
      this.toast.success('Removed from favorites.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

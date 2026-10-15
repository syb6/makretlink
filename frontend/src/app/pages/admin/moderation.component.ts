import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { img, money, statusBadge, statusLabel } from '../../core/utils';

@Component({
  selector: 'app-admin-moderation',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [],
  template: `
    <h2 class="section-title mb-3">Moderation</h2>

    <h5 class="fw-bold mb-2">Products</h5>
    <div class="table-responsive card shadow-sm mb-4">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>Product</th><th>Farmer</th><th>Price</th><th>Status</th><th class="text-end">Action</th></tr></thead>
        <tbody>
          @for (p of d()?.products ?? []; track p.id) {
            <tr>
              <td class="small">{{ p.name }}</td>
              <td class="small">{{ p.farmer?.business_name }}</td>
              <td class="small">{{ money(p.price) }}</td>
              <td><span class="badge" [class]="'text-bg-' + badge(p.status)">{{ label(p.status) }}</span></td>
              <td class="text-end"><button class="btn btn-sm btn-outline-secondary" (click)="toggleProduct(p)">{{ p.status === 'active' ? 'Hide' : 'Show' }}</button></td>
            </tr>
          }
        </tbody>
      </table>
    </div>

    <div class="row g-4">
      <div class="col-lg-6">
        <h5 class="fw-bold mb-2">Product reviews</h5>
        @for (r of d()?.product_reviews ?? []; track r.id) {
          <div class="card mb-2"><div class="card-body py-2 small">
            <div class="d-flex justify-content-between">
              <div><strong>{{ r.customer?.name }}</strong> on {{ r.product?.name }}</div>
              <button class="btn btn-sm btn-outline-secondary" (click)="toggleProductReview(r)">{{ r.status === 'visible' ? 'Hide' : 'Show' }}</button>
            </div>
            <div class="rating-stars">{{ stars(r.rating) }} <span class="badge" [class]="'text-bg-' + badge(r.status)">{{ r.status }}</span></div>
            <div class="text-secondary">{{ r.comment }}</div>
          </div></div>
        }
      </div>
      <div class="col-lg-6">
        <h5 class="fw-bold mb-2">Farmer reviews</h5>
        @for (r of d()?.farmer_reviews ?? []; track r.id) {
          <div class="card mb-2"><div class="card-body py-2 small">
            <div class="d-flex justify-content-between">
              <div><strong>{{ r.customer?.name }}</strong> on {{ r.farmer?.business_name }}</div>
              <button class="btn btn-sm btn-outline-secondary" (click)="toggleFarmerReview(r)">{{ r.status === 'visible' ? 'Hide' : 'Show' }}</button>
            </div>
            <div class="rating-stars">{{ stars(r.rating) }} <span class="badge" [class]="'text-bg-' + badge(r.status)">{{ r.status }}</span></div>
            <div class="text-secondary">{{ r.comment }}</div>
          </div></div>
        }
      </div>
    </div>
  `,
})
export class AdminModerationComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  d = signal<any>(null);

  money = money; img = img; badge = statusBadge; label = statusLabel;
  stars(n: number): string { return '★'.repeat(n) + '☆'.repeat(5 - n); }

  ngOnInit(): void {
    void this.load();
  }

  async load(): Promise<void> {
    this.d.set(await this.api.get<any>('/admin/moderation'));
  }

  async toggleProduct(p: any): Promise<void> {
    try {
      await this.api.form('/admin/moderation/product/' + p.id, {});
      this.toast.success('Product ' + (p.status === 'active' ? 'hidden' : 'visible') + '.');
      void this.load();
    } catch (e: unknown) {
      this.toast.fromValidation(e);
    }
  }

  async toggleProductReview(r: any): Promise<void> {
    try {
      await this.api.form('/admin/moderation/product-review/' + r.id, {});
      this.toast.success('Review updated.');
      void this.load();
    } catch (e: unknown) {
      this.toast.fromValidation(e);
    }
  }

  async toggleFarmerReview(r: any): Promise<void> {
    try {
      await this.api.form('/admin/moderation/farmer-review/' + r.id, {});
      this.toast.success('Review updated.');
      void this.load();
    } catch (e: unknown) {
      this.toast.fromValidation(e);
    }
  }
}

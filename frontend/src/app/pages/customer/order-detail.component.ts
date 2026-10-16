import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { money, date, statusLabel, statusBadge } from '../../core/utils';

@Component({
  selector: 'app-customer-order-detail',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule],
  template: `
    @if (o(); as order) {
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <h2 class="section-title mb-0">{{ order.order_number }}</h2>
          <div class="text-muted small">Placed {{ date(order.placed_at, true) }} at {{ order.market_name }}</div>
        </div>
        <span class="badge fs-6" [class]="'text-bg-' + badge(order.status)">{{ label(order.status) }}</span>
      </div>

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-transparent fw-bold">Items</div>
            <table class="table table-sm align-middle mb-0">
              <tbody>
                @for (i of order.items; track i.id) {
                  <tr>
                    <td>{{ i.product_name }}</td>
                    <td class="text-muted small">{{ i.quantity }} {{ i.unit }} × {{ money(i.unit_price) }}</td>
                    <td class="text-end fw-semibold">{{ money(i.subtotal) }}</td>
                    <td class="text-end" style="width:130px">
                      @if (order.status === 'completed') {
                        @if (reviewed(i)) {
                          <span class="badge bg-success">reviewed</span>
                        } @else {
                          <button class="btn btn-sm btn-outline-ml" (click)="rateStart(i)">Rate</button>
                        }
                      }
                    </td>
                  </tr>
                }
                <tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold">{{ money(order.total_amount) }}</td></tr>
              </tbody>
            </table>
          </div>

          @if (order.status === 'completed') {
            <div class="card shadow-sm mb-3">
              <div class="card-header bg-transparent fw-bold">Rate this farmer</div>
              <div class="card-body">
                @if (existingFarmer()) {
                  <div class="rating-stars"><i class="bi bi-star-fill"></i> {{ existingFarmer().rating }}/5 — "{{ existingFarmer().comment }}"</div>
                } @else if (rateTarget() === 'farmer') {
                  <div class="mb-2">@for (i of [1,2,3,4,5]; track i) {
                    <button type="button" class="btn btn-link p-0 fs-4 text-warning" (click)="farmerRating.set(i)">
                      <i class="bi" [class.bi-star-fill]="i <= farmerRating()" [class.bi-star]="i > farmerRating()"></i>
                    </button>
                  }</div>
                  <input class="form-control mb-2" name="farmerComment" [(ngModel)]="farmerComment" placeholder="Optional comment">
                  <button class="btn btn-ml btn-sm" (click)="rateFarmer()">Submit review</button>
                } @else {
                  <button class="btn btn-outline-ml btn-sm" (click)="rateTarget.set('farmer')">Write a farmer review</button>
                }
              </div>
            </div>
          }

          @if (order.customer_note) {
            <div class="alert alert-info small"><strong>Your note:</strong> {{ order.customer_note }}</div>
          }
        </div>

        <div class="col-lg-4">
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-transparent fw-bold">Pickup</div>
            <div class="card-body small">
              <div class="mb-1"><i class="bi bi-calendar2 me-2 text-success"></i>{{ date(order.pickup_date) }}</div>
              <div class="mb-1"><i class="bi bi-clock me-2 text-success"></i>{{ order.pickup_start_time }} – {{ order.pickup_end_time }}</div>
              <div class="mb-1"><i class="bi bi-shop me-2 text-success"></i>{{ order.farmer_name }}</div>
              @if (order.slot?.cutoff_at) { <div class="text-secondary">Order by {{ date(order.slot.cutoff_at, true) }}</div> }
            </div>
          </div>

          <div class="card shadow-sm mb-3">
            <div class="card-header bg-transparent fw-bold">Status history</div>
            <div class="card-body">
              <ul class="list-unstyled mb-0 small">
                @for (h of order.status_histories; track h.id) {
                  <li class="mb-2">
                    <span class="badge" [class]="'text-bg-' + badge(h.status)">{{ label(h.status) }}</span>
                    <div class="text-secondary">{{ h.note }} · {{ date(h.changed_at, true) }}@if (h.changed_by) { · {{ h.changed_by }}}</div>
                  </li>
                }
              </ul>
            </div>
          </div>

          @if (order.status === 'placed' || order.status === 'accepted') {
            <div class="d-grid gap-2">
              <button class="btn btn-outline-primary" (click)="reorder()">Reorder</button>
              <button class="btn btn-outline-danger" (click)="cancel()">Cancel order</button>
            </div>
          } @else if (order.status !== 'cancelled' && order.status !== 'declined') {
            <div class="d-grid"><button class="btn btn-outline-primary" (click)="reorder()">Reorder</button></div>
          }
        </div>
      </div>

      @if (rateItem(); as item) {
        <div class="modal d-block" style="background: rgba(0,0,0,.4)" (click)="rateItem.set(null)">
          <div class="modal-dialog" (click)="$event.stopPropagation()">
            <div class="modal-content">
              <div class="modal-header"><h5 class="modal-title">Review {{ item.product_name }}</h5></div>
              <div class="modal-body">
                <div class="mb-2">@for (i of [1,2,3,4,5]; track i) {
                  <button type="button" class="btn btn-link p-0 fs-4 text-warning" (click)="itemRating.set(i)">
                    <i class="bi" [class.bi-star-fill]="i <= itemRating()" [class.bi-star]="i > itemRating()"></i>
                  </button>
                }</div>
                <input class="form-control" name="itemComment" [(ngModel)]="itemComment" placeholder="Optional comment">
              </div>
              <div class="modal-footer">
                <button class="btn btn-outline-secondary" (click)="rateItem.set(null)">Close</button>
                <button class="btn btn-ml" (click)="rateProduct(item)">Submit</button>
              </div>
            </div>
          </div>
        </div>
      }
    } @else {
      <div class="text-center py-5"><div class="spinner-border text-success"></div></div>
    }
  `,
})
export class CustomerOrderDetailComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  private route = inject(ActivatedRoute);

  o = signal<any>(null);
  existingProductReviews = signal<Record<string, any>>({});
  existingFarmer = signal<any>(null);

  rateItem = signal<any>(null);
  itemRating = signal(5);
  itemComment = '';
  rateTarget = signal<'farmer' | null>(null);
  farmerRating = signal(5);
  farmerComment = '';

  money = money; date = date; label = statusLabel; badge = statusBadge;

  ngOnInit(): void {
    void this.load();
  }

  async load(): Promise<void> {
    const d = await this.api.get<any>('/customer/orders/' + this.route.snapshot.paramMap.get('id'));
    this.o.set(d.order);
    const map: Record<string, any> = {};
    for (const [pid, r] of Object.entries(d.existing_product_reviews ?? {})) map[pid] = r;
    this.existingProductReviews.set(map);
    this.existingFarmer.set(d.existing_farmer_review ?? null);
  }

  reviewed(item: any): boolean {
    return !!this.existingProductReviews()[String(item.product_id)];
  }

  rateStart(item: any): void {
    this.itemRating.set(5);
    this.itemComment = '';
    this.rateItem.set(item);
  }

  async rateProduct(item: any): Promise<void> {
    try {
      await this.api.form('/customer/orders/' + this.o().id + '/review/product/' + item.id, {
        rating: this.itemRating(), comment: this.itemComment,
      });
      this.toast.success('Thanks for your review!');
      this.rateItem.set(null);
      void this.load();
    } catch (e: unknown) {
      this.toast.fromValidation(e);
    }
  }

  async rateFarmer(): Promise<void> {
    try {
      await this.api.form('/customer/orders/' + this.o().id + '/review/farmer', {
        rating: this.farmerRating(), comment: this.farmerComment,
      });
      this.toast.success('Thanks for rating this farmer!');
      void this.load();
    } catch (e: unknown) {
      this.toast.fromValidation(e);
    }
  }

  async cancel(): Promise<void> {
    try {
      await this.api.form('/customer/orders/' + this.o().id + '/cancel', {});
      this.toast.success('Order cancelled.');
      void this.load();
    } catch (e: unknown) {
      this.toast.fromValidation(e);
    }
  }

  async reorder(): Promise<void> {
    try {
      await this.api.form('/customer/orders/' + this.o().id + '/reorder', {});
      this.toast.success('Items added to cart.');
    } catch (e: unknown) {
      this.toast.fromValidation(e);
    }
  }
}

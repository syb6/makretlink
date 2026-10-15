import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { money, date, statusLabel, statusBadge } from '../../core/utils';

@Component({
  selector: 'app-farmer-order-detail',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [],
  template: `
    @if (o(); as order) {
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <h2 class="section-title mb-0">{{ order.order_number }}</h2>
          <div class="text-muted small">Placed {{ date(order.placed_at, true) }}</div>
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
                  <tr><td>{{ i.product_name }}</td><td class="text-muted small">{{ i.quantity }} {{ i.unit }} × {{ money(i.unit_price) }}</td><td class="text-end fw-semibold">{{ money(i.subtotal) }}</td></tr>
                }
                <tr><td colspan="2" class="text-end fw-bold">Total</td><td class="text-end fw-bold">{{ money(order.total_amount) }}</td></tr>
              </tbody>
            </table>
          </div>

          @if (order.status_histories?.length) {
            <div class="card shadow-sm">
              <div class="card-header bg-transparent fw-bold">History</div>
              <div class="card-body">
                <ul class="list-unstyled mb-0 small">
                  @for (h of order.status_histories; track h.id) {
                    <li class="mb-2"><span class="badge" [class]="'text-bg-' + badge(h.status)">{{ label(h.status) }}</span> {{ h.note }} · {{ date(h.changed_at, true) }}</li>
                  }
                </ul>
              </div>
            </div>
          }
        </div>

        <div class="col-lg-4">
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-transparent fw-bold">Customer</div>
            <div class="card-body small">
              <div class="mb-1 fw-semibold">{{ order.customer?.user?.name }}</div>
              <div class="mb-1"><i class="bi bi-telephone me-2 text-success"></i>{{ order.customer?.user?.phone }}</div>
              <div class="mb-1"><i class="bi bi-envelope me-2 text-success"></i>{{ order.customer?.user?.email }}</div>
            </div>
          </div>

          <div class="card shadow-sm mb-3">
            <div class="card-header bg-transparent fw-bold">Pickup</div>
            <div class="card-body small">
              <div class="mb-1"><i class="bi bi-calendar2 me-2 text-success"></i>{{ date(order.pickup_date) }}</div>
              <div class="mb-1"><i class="bi bi-clock me-2 text-success"></i>{{ order.pickup_start_time }} – {{ order.pickup_end_time }}</div>
              @if (order.slot?.cutoff_at) { <div class="text-secondary">Cutoff {{ date(order.slot.cutoff_at, true) }}</div> }
            </div>
          </div>

          <div class="d-grid gap-2">
            @switch (true) {
              @case (order.status === 'placed') {
                <button class="btn btn-success" (click)="act('accept', 'Order accepted.')">Accept</button>
                <button class="btn btn-outline-danger" (click)="act('decline', 'Order declined.')">Decline</button>
              }
              @case (order.status === 'accepted') {
                <button class="btn btn-info" (click)="act('ready', 'Marked as ready for pickup.')">Mark ready</button>
              }
              @case (order.status === 'ready_for_pickup') {
                <button class="btn btn-success" (click)="act('complete', 'Order completed.')">Complete (paid in person)</button>
              }
              @default { <div class="alert alert-light small mb-0">No actions available for this status.</div> }
            }
          </div>
        </div>
      </div>
    } @else {
      <div class="text-center py-5"><div class="spinner-border text-success"></div></div>
    }
  `,
})
export class FarmerOrderDetailComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  private route = inject(ActivatedRoute);

  o = signal<any>(null);
  money = money; date = date; label = statusLabel; badge = statusBadge;

  ngOnInit(): void {
    void this.load();
  }

  async load(): Promise<void> {
    const d = await this.api.get<any>('/farmer/orders/' + this.route.snapshot.paramMap.get('id'));
    this.o.set(d.order);
  }

  async act(kind: 'accept' | 'decline' | 'ready' | 'complete', msg: string): Promise<void> {
    try {
      await this.api.form('/farmer/orders/' + this.o().id + '/' + kind, {});
      this.toast.success(msg);
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

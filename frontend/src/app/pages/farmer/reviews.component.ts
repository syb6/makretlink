import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { date, statusBadge } from '../../core/utils';

@Component({
  selector: 'app-farmer-reviews',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule],
  template: `
    <h2 class="section-title mb-3">Customer reviews</h2>

    @for (r of rows(); track r.id) {
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <div class="rating-stars">
                @for (i of [1,2,3,4,5]; track i) { <i class="bi" [class.bi-star-fill]="i <= r.rating" [class.bi-star]="i > r.rating"></i> }
              </div>
              <div class="small fw-semibold">{{ r.customer?.name }} · <span class="text-muted">{{ date(r.createdDay) }}</span></div>
            </div>
            <span class="badge " [class]="'text-bg-' + badge(r.status)">{{ r.status }}</span>
          </div>
          <p class="mb-2 mt-2">{{ r.comment }}</p>
          @if (r.farmer_reply) {
            <div class="chat-widget-answer p-2 small"><strong>Your reply:</strong> {{ r.farmer_reply }}</div>
          } @else {
            <div class="input-group input-group-sm">
              <input class="form-control" name="reply-{{ r.id }}" [(ngModel)]="drafts[r.id]" placeholder="Reply to this customer…">
              <button class="btn btn-success" (click)="reply(r)">Post reply</button>
            </div>
          }
        </div>
      </div>
    } @empty {
      <p class="text-muted">No reviews yet.</p>
    }

    @if (lastPage() > 1) {
      <nav><ul class="pagination justify-content-center">
        @for (p of pages(); track p) {
          <li class="page-item" [class.active]="p === page()"><button class="page-link" (click)="page.set(p); load()">{{ p }}</button></li>
        }
      </ul></nav>
    }
  `,
})
export class FarmerReviewsComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  rows = signal<any[]>([]);
  page = signal(1);
  lastPage = signal(1);
  drafts: Record<number, string> = {};

  date = date; badge = statusBadge;

  ngOnInit(): void {
    void this.load();
  }

  pages(): number[] { return Array.from({ length: this.lastPage() }, (_, i) => i + 1); }

  async load(): Promise<void> {
    const d = await this.api.get<any>('/farmer/reviews?page=' + this.page());
    this.rows.set(d.rows);
    this.page.set(d.page);
    this.lastPage.set(d.last_page);
  }

  async reply(r: any): Promise<void> {
    const text = this.drafts[r.id];
    if (!text) {
      this.toast.error('Write a reply first.');
      return;
    }
    try {
      await this.api.form('/farmer/reviews/' + r.id + '/reply', { farmer_reply: text });
      this.toast.success('Reply posted.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

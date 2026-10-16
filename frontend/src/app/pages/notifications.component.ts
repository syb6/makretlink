import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../core/api.service';
import { AuthService } from '../core/auth.service';
import { date } from '../core/utils';
import { LoadingStateComponent } from '../layout/loading-state.component';
import { ToastService } from '../core/toast.service';

@Component({
  selector: 'app-notifications',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [LoadingStateComponent],
  template: `
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="section-title mb-0">Notifications</h2>
      @if (unread() > 0) {
        <button class="btn btn-sm btn-outline-ml" (click)="markAll()">Mark all read ({{ unread() }})</button>
      }
    </div>

    <div class="list-group">
      @for (n of items(); track n.id) {
        <div class="list-group-item d-flex justify-content-between align-items-start"
             [class.bg-light]="!n.read_at" style="cursor: pointer" (click)="open(n)">
          <div>
            <div class="fw-semibold">{{ n.title }}</div>
            @if (n.message) { <div class="small text-muted">{{ n.message }}</div> }
            <div class="small text-secondary">{{ date(n.created_at, true) }}</div>
          </div>
          @if (!n.read_at) { <span class="badge bg-danger rounded-pill">new</span> }
        </div>
      } @empty {
        <div class="list-group-item text-muted">
          @if (loaded()) { No notifications yet. }
          @else { <app-loading-state label="Loading notifications…" /> }
        </div>
      }
    </div>
  `,
})
export class NotificationsComponent implements OnInit {
  private api = inject(ApiService);
  private auth = inject(AuthService);
  private router = inject(Router);
  private toast = inject(ToastService);

  items = signal<any[]>([]);
  unread = signal(0);
  loaded = signal(false);

  date = date;

  ngOnInit(): void {
    void this.load();
  }

  async load(): Promise<void> {
    this.loaded.set(false);
    try {
      const d = await this.api.get<any>('/notifications');
      this.items.set(d.items);
      this.unread.set(d.unread);
      void this.auth.load();
    } catch (e) {
      this.toast.fromValidation(e);
    } finally {
      this.loaded.set(true);
    }
  }

  async open(n: any): Promise<void> {
    if (!n.read_at) {
      await this.api.post('/notifications/' + n.id + '/read');
    }
    this.router.navigateByUrl(n.url?.startsWith('http') ? new URL(n.url).pathname : (n.url ?? '/'));
  }

  async markAll(): Promise<void> {
    await this.api.post('/notifications/read-all');
    void this.load();
  }
}

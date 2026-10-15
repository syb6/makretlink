import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { date } from '../../core/utils';

@Component({
  selector: 'app-admin-feedback',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [],
  template: `
    <h2 class="section-title mb-3">Feedback inbox @if (unread() > 0) { <span class="badge text-bg-danger fs-7">{{ unread() }} unread</span> }</h2>

    @for (m of rows(); track m.id) {
      <div class="card shadow-sm mb-2" [class.border-success]="!m.read_at">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="fw-semibold">{{ m.name }} <span class="text-muted small">&lt;{{ m.email }}&gt;</span></div>
              <div class="small text-secondary">{{ date(m.created_at, true) }}</div>
            </div>
            <div class="d-flex gap-1">
              <button class="btn btn-sm" [class.btn-outline-success]="!m.read_at" [class.btn-outline-secondary]="!!m.read_at" (click)="toggle(m)">
                {{ m.read_at ? 'Mark unread' : 'Mark read' }}
              </button>
              <button class="btn btn-sm btn-outline-danger" (click)="remove(m)"><i class="bi bi-trash"></i></button>
            </div>
          </div>
          <p class="mb-0 mt-2 {{ m.read_at ? 'text-secondary' : '' }}">{{ m.message }}</p>
        </div>
      </div>
    } @empty {
      <p class="text-muted">Inbox is empty.</p>
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
export class AdminFeedbackComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  rows = signal<any[]>([]);
  unread = signal(0);
  page = signal(1);
  lastPage = signal(1);

  date = date;

  ngOnInit(): void {
    void this.load();
  }

  pages(): number[] { return Array.from({ length: this.lastPage() }, (_, i) => i + 1); }

  async load(): Promise<void> {
    const d = await this.api.get<any>('/admin/feedback?page=' + this.page());
    this.rows.set(d.rows);
    this.unread.set(d.unread_count);
    this.page.set(d.page);
    this.lastPage.set(d.last_page);
  }

  async toggle(m: any): Promise<void> {
    try {
      await this.api.form('/admin/feedback/' + m.id + '/toggle-read', {});
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  async remove(m: any): Promise<void> {
    try {
      await this.api.del('/admin/feedback/' + m.id);
      this.toast.success('Feedback message deleted.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

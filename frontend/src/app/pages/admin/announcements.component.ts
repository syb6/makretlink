import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { date, statusBadge, statusLabel } from '../../core/utils';

@Component({
  selector: 'app-admin-announcements',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule],
  template: `
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="section-title mb-0">Announcements</h2>
      <button class="btn btn-ml btn-sm" (click)="startNew()"><i class="bi bi-plus-lg me-1"></i>New announcement</button>
    </div>

    @if (editing() !== null) {
      <div class="modal d-block" style="background: rgba(0,0,0,.4)" (click)="editing.set(null)">
        <div class="modal-dialog" (click)="$event.stopPropagation()">
          <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ editing().id ? 'Edit announcement' : 'New announcement' }}</h5></div>
            <div class="modal-body">
              <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" [(ngModel)]="form.title" required maxlength="180"></div>
              <div class="mb-3"><label class="form-label">Message</label><textarea class="form-control" name="message" [(ngModel)]="form.message" rows="4" required maxlength="5000"></textarea></div>
              <div class="mb-2"><label class="form-label">Status</label>
                <select class="form-select" name="status" [(ngModel)]="form.status">
                  <option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-outline-secondary" (click)="editing.set(null)">Cancel</button>
              <button class="btn btn-ml" (click)="save()">Save</button>
            </div>
          </div>
        </div>
      </div>
    }

    @for (a of rows(); track a.id) {
      <div class="card shadow-sm mb-2">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong>{{ a.title }}</strong> <span class="badge ms-1" [class]="'text-bg-' + badge(a.status)">{{ label(a.status) }}</span>
              <div class="small text-muted">{{ date(a.published_at ?? a.created_at, true) }} by {{ a.author?.name }}</div>
            </div>
            <div>
              <button class="btn btn-sm btn-outline-ml me-1" (click)="startEdit(a)">Edit</button>
              <button class="btn btn-sm btn-outline-danger" (click)="remove(a)"><i class="bi bi-trash"></i></button>
            </div>
          </div>
          <p class="mb-0 mt-2 small text-secondary">{{ a.message }}</p>
        </div>
      </div>
    } @empty { <p class="text-muted">No announcements yet.</p> }

    @if (lastPage() > 1) {
      <nav><ul class="pagination justify-content-center">
        @for (p of pages(); track p) {
          <li class="page-item" [class.active]="p === page()"><button class="page-link" (click)="page.set(p); load()">{{ p }}</button></li>
        }
      </ul></nav>
    }
  `,
})
export class AdminAnnouncementsComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  rows = signal<any[]>([]);
  page = signal(1);
  lastPage = signal(1);
  editing = signal<any>(null);
  form: any = {};

  date = date; badge = statusBadge; label = statusLabel;

  ngOnInit(): void {
    void this.load();
  }

  pages(): number[] { return Array.from({ length: this.lastPage() }, (_, i) => i + 1); }

  async load(): Promise<void> {
    const d = await this.api.get<any>('/admin/announcements?page=' + this.page());
    this.rows.set(d.rows);
    this.page.set(d.page);
    this.lastPage.set(d.last_page);
  }

  startNew(): void {
    this.form = { title: '', message: '', status: 'draft' };
    this.editing.set({});
  }

  startEdit(a: any): void {
    this.form = { title: a.title, message: a.message, status: a.status };
    this.editing.set(a);
  }

  async save(): Promise<void> {
    try {
      const id = this.editing().id;
      if (id) {
        await this.api.form('/admin/announcements/' + id, this.form);
        this.toast.success('Announcement updated.');
      } else {
        await this.api.form('/admin/announcements', this.form);
        this.toast.success('Announcement created.');
      }
      this.editing.set(null);
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  async remove(a: any): Promise<void> {
    try {
      await this.api.del('/admin/announcements/' + a.id);
      this.toast.success('Announcement deleted.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

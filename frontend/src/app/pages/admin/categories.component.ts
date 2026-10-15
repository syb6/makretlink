import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { imageError, img, statusLabel } from '../../core/utils';

@Component({
  selector: 'app-admin-categories',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule],
  template: `
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="section-title mb-0">Categories</h2>
      <button class="btn btn-success btn-sm" (click)="startNew()"><i class="bi bi-plus-lg me-1"></i>New category</button>
    </div>

    @if (editing() !== null) {
      <div class="modal d-block" style="background: rgba(0,0,0,.4)" (click)="editing.set(null)">
        <div class="modal-dialog" (click)="$event.stopPropagation()">
          <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ editing().id ? 'Edit category' : 'New category' }}</h5></div>
            <div class="modal-body">
              <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" [(ngModel)]="form.name" required maxlength="100"></div>
              <div class="mb-3"><label class="form-label">Status</label>
                <select class="form-select" name="status" [(ngModel)]="form.status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
              </div>
              <div class="mb-2"><label class="form-label">Photo (min 200×200)</label><input class="form-control" type="file" accept="image/png,image/jpeg,image/webp" (change)="image.set($any($event.target).files?.[0] ?? null)"></div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-outline-secondary" (click)="editing.set(null)">Cancel</button>
              <button class="btn btn-success" (click)="save()" [disabled]="busy()">@if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }Save</button>
            </div>
          </div>
        </div>
      </div>
    }

    <div class="table-responsive card shadow-sm">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Category</th><th>Products</th><th>Status</th><th class="text-end"></th></tr></thead>
        <tbody>
          @for (c of rows(); track c.id) {
            <tr>
              <td><div class="d-flex align-items-center gap-2">
                <img [src]="img('category', c.image)" (error)="onImageError($event, 'category')" style="width:40px;height:40px;object-fit:cover" class="rounded-circle" alt="">
                <span class="fw-semibold">{{ c.name }}</span>
              </div></td>
              <td>{{ c.products_count }}</td>
              <td><span class="badge" [class]="c.status === 'active' ? 'text-bg-success' : 'text-bg-secondary'">{{ label(c.status) }}</span></td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-success me-1" (click)="startEdit(c)">Edit</button>
                <button class="btn btn-sm btn-outline-danger" (click)="remove(c)"><i class="bi bi-trash"></i></button>
              </td>
            </tr>
          }
        </tbody>
      </table>
    </div>

    @if (lastPage() > 1) {
      <nav class="mt-3"><ul class="pagination justify-content-center">
        @for (p of pages(); track p) {
          <li class="page-item" [class.active]="p === page()"><button class="page-link" (click)="page.set(p); load()">{{ p }}</button></li>
        }
      </ul></nav>
    }
  `,
})
export class AdminCategoriesComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  rows = signal<any[]>([]);
  page = signal(1);
  lastPage = signal(1);
  editing = signal<any>(null);
  form: any = {};
  image = signal<File | null>(null);
  busy = signal(false);

  img = img; label = statusLabel; onImageError = imageError;

  ngOnInit(): void {
    void this.load();
  }

  pages(): number[] { return Array.from({ length: this.lastPage() }, (_, i) => i + 1); }

  async load(): Promise<void> {
    const d = await this.api.get<any>('/admin/categories?page=' + this.page());
    this.rows.set(d.rows);
    this.page.set(d.page);
    this.lastPage.set(d.last_page);
  }

  startNew(): void {
    this.form = { name: '', status: 'active' };
    this.image.set(null);
    this.editing.set({});
  }

  startEdit(c: any): void {
    this.form = { name: c.name, status: c.status };
    this.image.set(null);
    this.editing.set(c);
  }

  async save(): Promise<void> {
    this.busy.set(true);
    try {
      const id = this.editing().id;
      if (id) {
        await this.api.upload('/admin/categories/' + id, this.form, { image: this.image() });
        this.toast.success('Category updated.');
      } else {
        await this.api.upload('/admin/categories', this.form, { image: this.image() });
        this.toast.success('Category created.');
      }
      this.editing.set(null);
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }

  async remove(c: any): Promise<void> {
    try {
      const res = await this.api.del<{ flash: Record<string, string> }>('/admin/categories/' + c.id);
      if (res.flash?.['error']) this.toast.error(res.flash['error']);
      else this.toast.success('Category removed.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

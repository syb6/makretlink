import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { imageError, money, img, statusBadge, statusLabel } from '../../core/utils';

@Component({
  selector: 'app-farmer-products',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule],
  template: `
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="section-title mb-0">Products</h2>
      <button class="btn btn-success btn-sm" (click)="startNew()"><i class="bi bi-plus-lg me-1"></i>New product</button>
    </div>

    @if (editing(); as p) {
      <div class="modal d-block" style="background: rgba(0,0,0,.4)" (click)="editing.set(null)">
        <div class="modal-dialog modal-lg" (click)="$event.stopPropagation()">
          <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ p.id ? 'Edit product' : 'New product' }}</h5></div>
            <div class="modal-body">
              <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Name</label><input class="form-control" name="name" [(ngModel)]="form.name" required maxlength="150"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Category</label>
                  <select class="form-select" name="category_id" [(ngModel)]="form.category_id">
                    <option value="">— none —</option>
                    @for (c of cats(); track c.id) { <option [value]="c.id">{{ c.name }}</option> }
                  </select>
                </div>
                <div class="col-md-4 mb-3"><label class="form-label">Price</label><input class="form-control" type="number" step="0.01" min="0.01" name="price" [(ngModel)]="form.price" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Unit</label><input class="form-control" name="unit" [(ngModel)]="form.unit" placeholder="kg, box, bunch…" required maxlength="30"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Status</label>
                  <select class="form-select" name="status" [(ngModel)]="form.status">
                    <option value="active">Active</option><option value="inactive">Inactive</option>
                  </select>
                </div>
                <div class="col-12 mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" [(ngModel)]="form.description" rows="2" maxlength="2000"></textarea></div>
                <div class="col-md-8 mb-3"><label class="form-label">Photo (jpg/png/webp, ≥64px)</label><input class="form-control" type="file" accept="image/png,image/jpeg,image/webp" (change)="image.set($any($event.target).files?.[0] ?? null)"></div>
                @if (!p.id) {
                  <div class="col-md-6 mb-3"><label class="form-label">Stock it at (launch)</label>
                    <select class="form-select form-select-sm" name="initial_market_id" [(ngModel)]="form.initial_market_id">
                      <option value="">— none —</option>
                      @for (s of stalls(); track s.id) { <option [value]="s.id">{{ s.market?.name }}</option> }
                    </select>
                  </div>
                  <div class="col-md-6 mb-3"><label class="form-label">Launch quantity</label><input class="form-control" type="number" min="0" step="0.5" name="initial_quantity" [(ngModel)]="form.initial_quantity"></div>
                }
              </div>
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
        <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @for (p of rows(); track p.id) {
            <tr [class.opacity-50]="p.deleted_at">
              <td>
                <div class="d-flex align-items-center gap-2">
                  <img [src]="img('product', p.image)" (error)="onImageError($event, 'product')" style="width:40px;height:40px;object-fit:cover" class="rounded" alt="">
                  <div><div class="fw-semibold">{{ p.name }}</div><div class="small text-muted">{{ p.unit }}</div></div>
                </div>
              </td>
              <td class="small">{{ p.category?.name ?? '—' }}</td>
              <td>{{ money(p.price) }}</td>
              <td>
                @if (p.deleted_at) { <span class="badge text-bg-danger">deleted</span> }
                @else { <span class="badge" [class]="'text-bg-' + badge(p.status)">{{ label(p.status) }}</span> }
              </td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-success me-1" (click)="startEdit(p)">Edit</button>
                @if (!p.deleted_at) {
                  <button class="btn btn-sm btn-outline-danger" (click)="remove(p)"><i class="bi bi-trash"></i></button>
                } @else {
                  <button class="btn btn-sm btn-outline-secondary" (click)="restore(p)">Restore</button>
                }
              </td>
            </tr>
          } @empty { <tr><td colspan="5" class="text-muted">No products yet.</td></tr> }
        </tbody>
      </table>
    </div>

    @if (lastPage() > 1) {
      <nav class="mt-3"><ul class="pagination justify-content-center">
        @for (pg of pages(); track pg) {
          <li class="page-item" [class.active]="pg === page()"><button class="page-link" (click)="page.set(pg); load()">{{ pg }}</button></li>
        }
      </ul></nav>
    }
  `,
})
export class FarmerProductsComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  rows = signal<any[]>([]);
  cats = signal<any[]>([]);
  stalls = signal<any[]>([]);
  page = signal(1);
  lastPage = signal(1);
  editing = signal<any>(null);
  form: any = {};
  image = signal<File | null>(null);
  busy = signal(false);

  money = money; img = img; badge = statusBadge; label = statusLabel; onImageError = imageError;

  ngOnInit(): void {
    void this.load();
  }

  pages(): number[] { return Array.from({ length: this.lastPage() }, (_, i) => i + 1); }

  async load(): Promise<void> {
    const d = await this.api.get<any>('/farmer/products?page=' + this.page());
    this.rows.set(d.rows);
    this.cats.set(d.categories);
    this.stalls.set(d.stalls);
    this.page.set(d.page);
    this.lastPage.set(d.last_page);
  }

  startNew(): void {
    this.form = { name: '', category_id: '', price: '', unit: 'kg', description: '', status: 'active', initial_market_id: '', initial_quantity: '' };
    this.image.set(null);
    this.editing.set({});
  }

  startEdit(p: any): void {
    this.form = {
      name: p.name, category_id: p.category?.id ?? '', price: p.price, unit: p.unit,
      description: p.description ?? '', status: p.status,
    };
    this.image.set(null);
    this.editing.set(p);
  }

  async save(): Promise<void> {
    const p = this.editing();
    this.busy.set(true);
    try {
      if (p.id) {
        await this.api.upload('/farmer/products/' + p.id, this.form, { image: this.image() });
        this.toast.success('Product updated.');
      } else {
        await this.api.upload('/farmer/products', this.form, { image: this.image() });
        this.toast.success('Product added.');
      }
      this.editing.set(null);
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }

  async remove(p: any): Promise<void> {
    try {
      await this.api.del<{ flash: Record<string, string> }>('/farmer/products/' + p.id);
      this.toast.success('Product removed.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  async restore(p: any): Promise<void> {
    try {
      await this.api.post('/farmer/products-restore/' + p.id);
      this.toast.success('Product restored.');
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

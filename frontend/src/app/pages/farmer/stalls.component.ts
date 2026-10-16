import { ChangeDetectionStrategy, Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';
import { ToastService } from '../../core/toast.service';
import { dayName } from '../../core/utils';

@Component({
  selector: 'app-farmer-stalls',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule],
  template: `
    <h2 class="section-title mb-3">My stalls</h2>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-md-5"><label class="form-label small">Join a market</label>
            <select class="form-select form-select-sm" name="market_id" [(ngModel)]="newMarket">
              @for (m of available(); track m.id) { <option [value]="m.id">{{ m.name }}</option> } @empty { <option disabled>Already at all active markets</option> }
            </select>
          </div>
          <div class="col-md-4"><input class="form-control form-control-sm" name="stall_name" [(ngModel)]="newStallName" placeholder="Stall name (optional)"></div>
          <div class="col-md-3 d-grid"><button class="btn btn-sm btn-ml" (click)="join()" [disabled]="!newMarket">Join market</button></div>
        </div>
      </div>
    </div>

    @for (s of stalls(); track s.id) {
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <h6 class="fw-bold mb-0">{{ s.market?.name }}</h6>
              <div class="small text-muted">{{ s.stall_name }} @if (s.stall_location) { · {{ s.stall_location }}}</div>
            </div>
            <div class="text-end">
              <span class="badge" [class]="s.status === 'active' ? 'text-bg-success' : 'text-bg-secondary'">{{ s.status === 'active' ? 'active' : 'paused' }}</span>
              <div class="mt-1"><button class="btn btn-sm btn-outline-secondary" (click)="toggle(s)">{{ s.status === 'active' ? 'Pause' : 'Resume' }}</button></div>
            </div>
          </div>

          <div class="row g-2">
            @for (day of [0,1,2,3,4,5,6]; track day) {
              @if (scheduleFor(s, day)) {
                <div class="col-6 col-md-4">
                  <div class="border rounded p-2 small">
                    <div class="fw-semibold">{{ dayName(day) }}</div>
                    <div class="d-flex gap-1 mt-1">
                      <input class="form-control form-control-sm" type="time" [ngModel]="scheduleFor(s, day).start_time"
                             (ngModelChange)="setStart(s, day, $event)" [id]="'st-' + scheduleKey(s, day)">
                      <input class="form-control form-control-sm" type="time" value="14:00" [class.d-none]="true">
                    </div>
                    <div class="d-flex gap-1 mt-1">
                      <button class="btn btn-outline-ml btn-sm" (click)="saveDay(s, day)">Save</button>
                      <button class="btn btn-outline-danger btn-sm" (click)="removeDay(s, day)">Remove day</button>
                    </div>
                  </div>
                </div>
              }
            }
          </div>

          <div class="mt-2 small text-muted">
            Add a day:
            @for (day of [0,1,2,3,4,5,6]; track day) {
              @if (!scheduleFor(s, day) && marketOpenDay(marketId(s), day)) {
                <button class="btn btn-sm btn-outline-secondary me-1" (click)="addDay(s, day)">{{ dayName(day) }}</button>
              }
            }
          </div>
        </div>
      </div>
    } @empty {
      <div class="alert alert-info">No stalls yet — join a market above to get started.</div>
    }
  `,
})
export class FarmerStallsComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  stalls = signal<any[]>([]);
  available = signal<any[]>([]);
  openDays = signal<any[]>([]);
  newMarket = '';
  newStallName = '';

  dayName = dayName;

  ngOnInit(): void {
    void this.load();
  }

  async load(): Promise<void> {
    const d = await this.api.get<any>('/farmer/stalls');
    this.stalls.set(d.stalls);
    this.available.set(d.available_markets);
    this.openDays.set(d.open_days);
  }

  marketId(s: any): number { return s.market?.id; }

  scheduleKey(s: any, d: number): string { return s.id + '-' + d; }

  scheduleFor(s: any, d: number): any {
    return s.schedules.find((x: any) => x.day_of_week === d && x.is_active) ?? null;
  }

  marketOpenDay(marketId: number, d: number): boolean {
    return this.openDays().some((x) => x.market_id === marketId && x.day_of_week === d && !x.is_closed);
  }

  setStart(s: any, d: number, val: string): void {
    const sc = this.scheduleFor(s, d);
    if (sc) sc._editableStart = val;
  }

  private endTimeAfter(start: string): string {
    const [h, m] = start.split(':').map(Number);
    return String((h + 2) % 24).padStart(2, '0') + ':' + String(m).padStart(2, '0');
  }

  async addDay(s: any, d: number): Promise<void> {
    try {
      const res = await this.api.form<{ flash: Record<string, string> }>('/farmer/stalls/' + s.id + '/schedule', {
        day_of_week: d, start_time: '08:00', end_time: '10:00',
      });
      this.toast.fromFlash(res.flash);
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  async saveDay(s: any, d: number): Promise<void> {
    const sc = this.scheduleFor(s, d)!;
    const start = this.timeOf(sc._editableStart ?? sc.start_time);
    const end = this.timeOf(sc.end_time);
    const useEnd = end > start ? end : this.endTimeAfter(start);
    try {
      const res = await this.api.form<{ flash: Record<string, string> }>('/farmer/stalls/' + s.id + '/schedule', {
        day_of_week: d, start_time: start, end_time: useEnd,
      });
      this.toast.fromFlash(res.flash);
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  private timeOf(v: any): string {
    const s = String(v);
    return s.length > 5 ? s.slice(11, 16) : s.slice(0, 5);
  }

  async removeDay(s: any, d: number): Promise<void> {
    try {
      const res = await this.api.form<{ flash: Record<string, string> }>('/farmer/stalls/' + s.id + '/schedule', {
        day_of_week: d, start_time: '', end_time: '',
      });
      this.toast.fromFlash(res.flash);
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  async join(): Promise<void> {
    if (!this.newMarket) return;
    try {
      const res = await this.api.form<{ flash: Record<string, string> }>('/farmer/stalls', {
        market_id: this.newMarket, stall_name: this.newStallName,
      });
      this.toast.fromFlash(res.flash);
      this.newStallName = '';
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }

  async toggle(s: any): Promise<void> {
    try {
      const res = await this.api.post<{ flash: Record<string, string> }>('/farmer/stalls/' + s.id + '/toggle');
      this.toast.fromFlash(res.flash);
      void this.load();
    } catch (e) {
      this.toast.fromValidation(e);
    }
  }
}

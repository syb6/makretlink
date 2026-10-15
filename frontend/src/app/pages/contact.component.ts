import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../core/api.service';
import { ToastService } from '../core/toast.service';

@Component({
  selector: 'app-contact',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule],
  template: `
    <div class="row g-4">
      <div class="col-lg-5">
        <h2 class="fw-bold">Contact us</h2>
        <p class="text-muted">Questions about an order, your stall or the platform? Send a message — the admin inbox reads them daily.</p>
        <ul class="list-unstyled small text-muted">
          <li class="mb-2"><i class="bi bi-envelope me-2 text-success"></i>hello&#64;marketlink.test</li>
          <li class="mb-2"><i class="bi bi-geo-alt me-2 text-success"></i>City Central Market, MarketLink HQ</li>
        </ul>
      </div>
      <div class="col-lg-7">
        <form class="card" (ngSubmit)="send()">
          <div class="card-body">
            @if (sent()) {
              <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i>Thanks for reaching out! Your message is on its way — we will get back to you soon.</div>
            }
            <div class="mb-3">
              <label class="form-label">Name</label>
              <input class="form-control" name="name" [(ngModel)]="f.name" required maxlength="120">
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input class="form-control" type="email" name="email" [(ngModel)]="f.email" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Message</label>
              <textarea class="form-control" name="message" [(ngModel)]="f.message" rows="5" required maxlength="2000"></textarea>
            </div>
            <button class="btn btn-success" [disabled]="busy()">@if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }Send message</button>
          </div>
        </form>
      </div>
    </div>
  `,
})
export class ContactComponent {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  f = { name: '', email: '', message: '' };
  busy = signal(false);
  sent = signal(false);

  async send(): Promise<void> {
    if (!this.f.name || !this.f.email || !this.f.message) {
      this.toast.error('All fields are required.');
      return;
    }
    this.busy.set(true);
    try {
      await this.api.form('/contact', this.f);
      this.sent.set(true);
      this.f = { name: '', email: '', message: '' };
    } catch (e) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }
}

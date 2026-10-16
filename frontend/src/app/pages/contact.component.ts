import { ChangeDetectionStrategy, Component, ElementRef, OnDestroy, inject, signal, viewChild } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../core/api.service';
import { ToastService } from '../core/toast.service';

declare const L: any;

/** Mirror of pages/contact.blade.php — page banner, contact card, flagship
 *  market schedule card, send-message form, Leaflet map of the market. */
@Component({
  selector: 'app-contact',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormsModule],
  template: `
    <section class="page-banner anim-up">
      <i class="bi bi-chat-heart page-banner-icon" aria-hidden="true"></i>
      <div class="container">
        <span class="badge-sub">GET IN TOUCH</span>
        <h1 class="section-title mb-1">Contact <span class="accent">Us</span></h1>
        <p class="section-subtitle mb-0">Questions about orders, selling, or partnerships? We're here to help.</p>
      </div>
    </section>
    <section class="py-4 py-md-5">
      <div class="container">
        <div class="row g-4 g-lg-5">
          <div class="col-lg-5">
            <div class="card-ml p-4 mb-4">
              <h5 class="fw-bold mb-3">Get in touch</h5>
              <p class="mb-2"><i class="bi bi-geo-alt me-2" style="color: var(--primary-dark)"></i>MarketLink — Gulshan-e-Iqbal, Karachi, Pakistan</p>
              <p class="mb-2"><i class="bi bi-envelope me-2" style="color: var(--primary-dark)"></i><a href="mailto:hello@marketlink.test" class="text-decoration-none contact-link">hello@marketlink.test</a></p>
              <p class="mb-2"><i class="bi bi-telephone me-2" style="color: var(--primary-dark)"></i><a href="tel:+922111111111" class="text-decoration-none contact-link">+92 21 111 111&nbsp;111</a></p>
              <p class="mb-0"><i class="bi bi-clock me-2" style="color: var(--primary-dark)"></i>Mon–Sat, 8:00 – 18:00</p>
            </div>

            @if (flagship(); as flagship) {
              <div class="card-ml p-4 mb-4">
                <h5 class="fw-bold mb-1"><i class="bi bi-shop me-2" style="color: var(--primary-dark)"></i>Visit the flagship market</h5>
                <p class="small text-muted mb-3">{{ flagship.address }}</p>
                <ul class="list-unstyled mb-0">
                  @for (s of flagship.schedules; track s.day) {
                    <li class="d-flex justify-content-between border-bottom py-2">
                      <span class="fw-semibold small">{{ s.day }}</span>
                      <span class="small text-muted">{{ s.opening_time }} – {{ s.closing_time }}</span>
                    </li>
                  }
                </ul>
              </div>
            }

            <form (ngSubmit)="send()" class="card-ml p-4">
              <div class="mb-3">
                <label class="form-label fw-semibold">Name</label>
                <input type="text" name="name" class="form-control" required [(ngModel)]="f.name">
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" required [(ngModel)]="f.email">
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Message</label>
                <textarea name="message" rows="4" class="form-control" required [(ngModel)]="f.message"></textarea>
              </div>
              <button class="btn btn-ml w-100" [disabled]="busy()">
                @if (busy()) { <span class="spinner-border spinner-border-sm me-1"></span> }
                Send message
              </button>
            </form>
          </div>
          <div class="col-lg-7">
            <div id="map" #mapEl></div>
            <p class="small text-muted mt-2">
              <i class="bi bi-info-circle me-1"></i>Our flagship <strong>Green Valley Community Market</strong> on University Road, Gulshan-e-Iqbal.
            </p>
          </div>
        </div>
      </div>
    </section>
  `,
})
export class ContactComponent implements OnDestroy {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  f = { name: '', email: '', message: '' };
  busy = signal(false);
  flagship = signal<{
    id: number; name: string; address: string; latitude: number | null; longitude: number | null;
    schedules: { day: string; opening_time: string; closing_time: string }[];
  } | null>(null);

  private mapObj: any = null;
  private mapHost = viewChild<ElementRef<HTMLDivElement>>('mapEl');

  constructor() {
    void this.api.get<any>('/contact').then((x) => {
      this.flagship.set(x.flagship ?? null);
      if (x.flagship) void this.initMap(x.flagship);
    }).catch(() => { /* contact page stays usable without the flagship card */ });
  }

  ngOnDestroy(): void {
    if (this.mapObj?.remove) this.mapObj.remove();
  }

  private async initMap(flagship: { latitude: number | null; longitude: number | null }): Promise<void> {
    const el: HTMLElement | undefined = this.mapHost()?.nativeElement;
    if (!el) return;
    const leaflet = await this.ensureLeaflet();
    if (!leaflet) return;
    const lat = Number(flagship.latitude ?? 24.9197) || 24.9197;
    const lng = Number(flagship.longitude ?? 67.0990) || 67.0990;
    this.mapObj = L.map(el).setView([lat, lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(this.mapObj);
    L.marker([lat, lng]).addTo(this.mapObj)
      .bindPopup('<strong>Green Valley Community Market</strong><br>University Road, Gulshan-e-Iqbal, Karachi')
      .openPopup();
  }

  private leafletPromise: Promise<boolean> | null = null;
  private ensureLeaflet(): Promise<boolean> {
    this.leafletPromise ??= new Promise((resolve) => {
      if (typeof (window as any).L !== 'undefined') return resolve(true);
      const css = document.createElement('link');
      css.rel = 'stylesheet';
      css.href = '/vendor/leaflet/leaflet.css';
      document.head.appendChild(css);
      const script = document.createElement('script');
      script.src = '/vendor/leaflet/leaflet.js';
      script.onload = () => resolve(true);
      script.onerror = () => {
        const cdnJs = document.createElement('script');
        cdnJs.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        cdnJs.onload = () => resolve(true);
        cdnJs.onerror = () => resolve(false);
        document.body.appendChild(cdnJs);
      };
      document.body.appendChild(script);
    });
    return this.leafletPromise;
  }

  async send(): Promise<void> {
    if (!this.f.name || !this.f.email || !this.f.message) {
      this.toast.error('All fields are required.');
      return;
    }
    this.busy.set(true);
    try {
      await this.api.form('/contact', this.f);
      this.toast.success('Thanks for reaching out! Your message is on its way — we will get back to you soon.');
      this.f = { name: '', email: '', message: '' };
    } catch (e) {
      this.toast.fromValidation(e);
    } finally {
      this.busy.set(false);
    }
  }
}

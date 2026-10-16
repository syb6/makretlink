import { ChangeDetectionStrategy, Component, computed, effect, inject, signal } from '@angular/core';
import { ApiService } from '../core/api.service';

@Component({
  selector: 'app-loading-indicator',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    @if (visible()) {
      <div class="site-loading-indicator" role="status" aria-live="polite">
        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        <span>Loading MarketLink…</span>
      </div>
    }
  `,
  styles: `
    .site-loading-indicator {
      position: fixed;
      z-index: 2090;
      inset: auto auto 1rem 1rem;
      display: flex;
      align-items: center;
      gap: 0.65rem;
      padding: 0.7rem 0.95rem;
      border: 1px solid var(--border-color, #e2e2d8);
      border-radius: 999px;
      background: var(--surface, #fff);
      color: var(--dark, #1c1c1c);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
      font-size: 0.85rem;
    }

    .spinner-border {
      width: 1rem;
      height: 1rem;
      color: var(--primary-dark, #66720f);
    }

    @media (prefers-reduced-motion: reduce) {
      .spinner-border { animation-duration: 1.5s; }
    }
  `,
})
export class LoadingIndicatorComponent {
  private readonly api = inject(ApiService);
  private readonly busy = computed(() => this.api.activeRequests() > 0);
  readonly visible = signal(false);

  constructor() {
    effect((onCleanup) => {
      if (!this.busy()) {
        this.visible.set(false);
        return;
      }

      const timer = window.setTimeout(() => this.visible.set(true), 250);
      onCleanup(() => window.clearTimeout(timer));
    });
  }
}

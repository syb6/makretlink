import { ChangeDetectionStrategy, Component, NgZone, inject, signal } from '@angular/core';

/**
 * Global chrome shared with the Blade layout:
 *  - thin scroll-progress bar pinned under the header (added by app.js on Blade pages)
 *  - .back-to-top button that appears after 300px of scrolling
 */
@Component({
  selector: 'app-back-to-top',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="scroll-progress" aria-hidden="true"></div>
    <button class="back-to-top" [class.show]="visible()" type="button"
            aria-label="Back to top" (click)="scrollTop()">
      <i class="bi bi-arrow-up"></i>
    </button>
  `,
})
export class BackToTopComponent {
  private zone = inject(NgZone);
  private smooth: boolean;
  visible = signal(false);

  constructor() {
    this.smooth = typeof window !== 'undefined'
      && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (typeof window === 'undefined') return;

    this.zone.runOutsideAngular(() => {
      let queued = false;

      const onScroll = () => {
        if (queued) return;
        queued = true;
        requestAnimationFrame(() => {
          queued = false;
          this.visible.set(window.scrollY > 300);

          const el = document.documentElement;
          const max = el.scrollHeight - el.clientHeight;
          const ratio = max > 0 ? el.scrollTop / max : 0;
          const bar = document.querySelector<HTMLElement>('.scroll-progress');
          if (bar) bar.style.width = ratio * 100 + '%';
        });
      };

      window.addEventListener('scroll', onScroll, { passive: true });
      window.addEventListener('resize', onScroll, { passive: true });
      onScroll();
    });
  }

  scrollTop(): void {
    window.scrollTo({ top: 0, behavior: this.smooth ? 'smooth' : 'auto' });
  }
}

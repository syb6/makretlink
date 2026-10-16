import { Injectable, NgZone, inject } from '@angular/core';

const REVEAL_SELECTOR = [
  '.stat-card', '.product-card', '.market-card', '.card-ml',
  '.dashboard-card', '.feature-card', '.step-card', '.category-card',
  '.order-item', '.metric-box', '.auth-card',
  '.notif-item', '.breadcrumb', '.page-banner .container',
  '.market-availability', '.empty',
  '.section-head', '.promo-content', '.promo-img-box', '.cta-box > h2',
  '.cta-box > p', '.cta-btns',
].join(', ');

/**
 * Motion layer for the SPA, matching the Blade app's public/js/app.js:
 *  - scroll-reveal on the same card selectors, with the same sibling stagger
 *  - animated count-up for .stat-number elements
 *  - reduced-motion is honored everywhere
 */
@Injectable({ providedIn: 'root' })
export class MotionService {
  private zone = inject(NgZone);
  private reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  private io: IntersectionObserver | null = null;
  private counterIO: IntersectionObserver | null = null;
  private mo: MutationObserver | null = null;
  private initialized = false;
  private handled = new WeakSet<Element>();
  private imageSources = new WeakMap<HTMLImageElement, string>();

  start(): void {
    if (this.initialized) return;
    this.initialized = true;

    if (!this.reduced) {
      document.documentElement.classList.add('js-anim');
    }

    this.io = 'IntersectionObserver' in window
      ? new IntersectionObserver((entries) => entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('in-view');
            this.io?.unobserve(entry.target);
          }
        }), { rootMargin: '0px 0px -8% 0px', threshold: 0.06 })
      : null;

    this.counterIO = 'IntersectionObserver' in window
      ? new IntersectionObserver((entries) => entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          this.countUp(entry.target);
          this.counterIO?.unobserve(entry.target);
        }), { threshold: 0.4 })
      : null;

    this.zone.runOutsideAngular(() => {
      this.scanWholePage();
      this.mo = new MutationObserver(() => this.scanWholePage());
      this.mo.observe(document.body, {
        attributes: true,
        attributeFilter: ['src', 'srcset'],
        childList: true,
        characterData: true,
        subtree: true,
      });
    });
  }

  stop(): void {
    this.mo?.disconnect();
    this.mo = null;
    this.io?.disconnect();
    this.io = null;
    this.counterIO?.disconnect();
    this.counterIO = null;
    this.initialized = false;
  }

  private scanWholePage(): void {
    document.querySelectorAll<HTMLImageElement>('img').forEach((image) => this.prepareImage(image));

    const matched = document.querySelectorAll<HTMLElement>(`${REVEAL_SELECTOR}, .stat-number`);

    matched.forEach((el) => {
      if (el.classList.contains('stat-number')) {
        this.prepareCounter(el);
        return;
      }
      if (this.handled.has(el) || (el as Element).closest('.modal') || el.classList.contains('reveal')) return;
      this.handled.add(el);
      this.prepareReveal(el);
    });
  }

  private prepareImage(image: HTMLImageElement): void {
    const source = `${image.getAttribute('src') ?? ''}\u0000${image.getAttribute('srcset') ?? ''}`;
    if (source === '\u0000' || this.imageSources.get(image) === source) return;
    this.imageSources.set(image, source);
    image.classList.remove('image-ready', 'image-failed');

    if (image.complete && image.naturalWidth > 0) {
      image.classList.add('image-ready');
      return;
    }

    image.classList.add('image-loading');
    image.addEventListener('load', () => {
      if (this.imageSources.get(image) !== source) return;
      image.classList.remove('image-loading', 'image-failed');
      image.classList.add('image-ready');
    }, { once: true });
    image.addEventListener('error', () => {
      if (this.imageSources.get(image) !== source) return;
      image.classList.remove('image-loading');
      image.classList.add('image-failed');
    }, { once: true });

    if (image.complete) {
      image.classList.remove('image-loading');
      image.classList.add('image-failed');
    }
  }

  private prepareReveal(el: HTMLElement): void {
    if (this.reduced || !this.io) {
      el.classList.add('reveal', 'in-view');
      return;
    }
    el.classList.add('reveal');
    const parent = el.parentElement;
    if (parent) {
      const index = Array.prototype.indexOf.call(parent.children, el);
      el.style.setProperty('--d', `${Math.min(Math.max(index, 0), 5) * 70}ms`);
    }
    this.io.observe(el);
  }

  private prepareCounter(el: HTMLElement): void {
    if (this.handled.has(el)) return;
    const raw = el.textContent?.trim() ?? '';
    if (!/^-?\d+(\.\d+)?$/.test(raw)) return;     // skip "Rs 1,234.00" style content
    if (Number(raw) === 0) return;                // wait for async stats to replace loading placeholders

    this.handled.add(el);
    el.setAttribute('data-target', raw);
    if (this.reduced || !this.counterIO) return;

    el.textContent = '0';
    this.counterIO.observe(el);
  }

  /** Ported countUp(): ease-out cubic over 900ms, keeps decimals. */
  private countUp(el: Element): void {
    const target = parseFloat(el.getAttribute('data-target') ?? '0');
    const decimals = (el.getAttribute('data-target') ?? '').split('.')[1]?.length ?? 0;
    const duration = 900;
    let start: number | null = null;

    const step = (time: number) => {
      start ??= time;
      const progress = Math.min((time - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = (target * eased).toFixed(decimals);
      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        el.textContent = el.getAttribute('data-target') ?? String(target);
      }
    };
    requestAnimationFrame(step);
  }
}

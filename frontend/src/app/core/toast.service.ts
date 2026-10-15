import { Injectable, signal } from '@angular/core';

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

export interface Toast {
  kind: 'success' | 'error' | 'info';
  text: string;
  title: string;
  messages: string[];
  ttl: number;
}

@Injectable({ providedIn: 'root' })
export class ToastService {
  readonly toasts = signal<Toast[]>([]);
  private readonly timers = new Map<Toast, {
    handle: ReturnType<typeof setTimeout> | null;
    startedAt: number;
    remaining: number;
  }>();

  show(
    kind: Toast['kind'],
    text: string,
    title = this.titleFor(kind),
    messages: string[] = [],
    ttl = 4500,
  ): void {
    const t = { kind, text, title, messages, ttl };
    this.toasts.update((l) => [...l, t]);
    if (ttl > 0) this.scheduleDismiss(t, ttl);
  }

  private scheduleDismiss(toast: Toast, delay: number): void {
    const timer = { handle: null as ReturnType<typeof setTimeout> | null, startedAt: Date.now(), remaining: delay };
    timer.handle = setTimeout(() => {
      this.timers.delete(toast);
      this.toasts.update((list) => list.filter((item) => item !== toast));
    }, delay);
    this.timers.set(toast, timer);
  }

  pause(toast: Toast): void {
    const timer = this.timers.get(toast);
    if (!timer?.handle) return;
    clearTimeout(timer.handle);
    timer.remaining = Math.max(0, timer.remaining - (Date.now() - timer.startedAt));
    timer.handle = null;
  }

  resume(toast: Toast): void {
    const timer = this.timers.get(toast);
    if (!timer || timer.handle || timer.remaining <= 0) return;
    this.scheduleDismiss(toast, timer.remaining);
  }

  dismiss(toast: Toast): void {
    const timer = this.timers.get(toast);
    if (timer?.handle) clearTimeout(timer.handle);
    this.timers.delete(toast);
    this.toasts.update((list) => list.filter((item) => item !== toast));
  }

  success(text: string): void {
    this.show('success', text);
  }

  error(text: string): void {
    this.show('error', text);
  }

  info(text: string): void {
    this.show('info', text);
  }

  private titleFor(kind: Toast['kind']): string {
    return kind === 'success' ? 'All good!' : kind === 'error' ? 'Something went wrong' : 'For your information';
  }

  /** Map a JsonFlashBridge flash envelope to toasts. */
  fromFlash(flash: Record<string, unknown> | undefined | null): void {
    if (!flash) return;
    if (flash['success']) this.success(String(flash['success']));
    if (flash['error']) this.error(String(flash['error']));
    if (flash['info']) this.info(String(flash['info']));
    if (flash['reset-url']) this.info('Dev mail driver: open ' + flash['reset-url']);
  }

  fromValidation(err: unknown): void {
    if (isRecord(err) && err['reported'] === true) return;

    const validationData = isRecord(err) && isRecord(err['validation'])
      ? err['validation']
      : null;
    const validation = validationData
      ? Object.values(validationData)
        .flatMap((messages) => Array.isArray(messages)
          ? messages.filter((message): message is string => typeof message === 'string')
          : typeof messages === 'string' ? [messages] : [])
      : [];

    if (validation.length) {
      const count = validation.length;
      this.show(
        'error',
        `${count} thing${count === 1 ? '' : 's'} need${count === 1 ? 's' : ''} fixing before you continue`,
        'Please fix the following',
        validation,
        0,
      );
      return;
    }
    this.error(
      isRecord(err) && typeof err['message'] === 'string'
        ? err['message']
        : err instanceof Error ? err.message : 'Something went wrong.',
    );
  }
}

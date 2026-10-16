import { Injectable, signal } from '@angular/core';

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

/** Ported verbatim from the <x-form-errors> component's $copy map. */
const FIX_HINTS: Record<string, string> = {
  name: 'Use letters and spaces, up to 120 characters — e.g. “Ayesha Khan”.',
  business_name: 'Give your stall a clear name, up to 150 characters — e.g. “Green Valley Farms”.',
  contact_person: 'Tell us who customers should ask for, up to 120 characters.',
  email: 'Use a real format like name@example.com — this is how you log in.',
  phone: 'Digits (and + or -) only, up to 30 characters — e.g. +92 300 1234567.',
  address: 'Write where you are located (max 1000 characters) so people can find you.',
  password: 'Use at least 8 characters; both password boxes must match exactly.',
  password_confirmation: 'Type the same password in both boxes — watch for caps lock.',
  price: 'Enter a positive amount in Rs, like 120.00 — no letters or symbols.',
  unit: 'How you sell it: kg, dozen, bunch, pc… (max 30 characters).',
  quantity: 'Enter how much stock you have, as a positive number.',
  category_id: 'Pick a category from the list.',
  image: 'Use a JPG, PNG or WebP image under 2 MB.',
  profile_image: 'Use a JPG, PNG or WebP image under 2 MB.',
  profile_photo: 'Use a JPG, PNG or WebP image under 2 MB.',
  message: 'Write your question (a few words up to ~1000 characters).',
  role: 'Choose whether you are joining as a Customer or a Farmer.',
  slots: 'Pick a pickup slot for every stall in your order.',
  pickup_date: 'Choose one of the available pickup windows.',
  title: 'A short headline, up to 150 characters.',
  subject: 'A short subject, up to 150 characters.',
  reply: 'Write a short reply (a few words up to ~500 characters).',
};

/** Auth failures keyed on 'email' — the address itself is fine, no hint. */
const AUTH_MESSAGES = new Set([
  'Invalid email or password.',
  'Your account has been deactivated. Please contact the administrator.',
]);

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
      // Every message carries the same "why + how to fix" copy the Blade
      // <x-form-errors /> box shows, matched by field key.
      const detailed = validationData
        ? Object.entries(validationData).flatMap(([field, messages]) =>
            (Array.isArray(messages) ? messages : [messages])
              .filter((message): message is string => typeof message === 'string')
              .map((message) => {
                const fix = AUTH_MESSAGES.has(message) ? null : FIX_HINTS[field];
                return fix ? `${message} — ${fix}` : message;
              }))
        : validation;
      const count = detailed.length;
      this.show(
        'error',
        `${count} thing${count === 1 ? '' : 's'} need${count === 1 ? 's' : ''} fixing before you continue`,
        'Please fix the following',
        detailed,
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

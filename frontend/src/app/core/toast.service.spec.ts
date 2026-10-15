import { afterEach, describe, expect, it, vi } from 'vitest';
import { ToastService } from './toast.service';

afterEach(() => {
  vi.useRealTimers();
});

describe('ToastService', () => {
  it('keeps all validation messages visible until dismissed', () => {
    vi.useFakeTimers();
    const service = new ToastService();

    service.fromValidation({
      message: 'The given data was invalid.',
      validation: {
        name: ['The name field is required.'],
        email: ['The email must be a valid email address.'],
      },
    });

    expect(service.toasts()).toEqual([{
      kind: 'error',
      title: 'Please fix the following',
      text: '2 things need fixing before you continue',
      messages: [
        'The name field is required.',
        'The email must be a valid email address.',
      ],
      ttl: 0,
    }]);
    expect(vi.getTimerCount()).toBe(0);
  });

  it('does not duplicate errors already reported by the API service', () => {
    const service = new ToastService();

    service.fromValidation(Object.assign(new Error('Server unavailable.'), { reported: true }));

    expect(service.toasts()).toEqual([]);
  });

  it('auto-dismisses normal flash toasts after their themed countdown', () => {
    vi.useFakeTimers();
    const service = new ToastService();

    service.success('Saved.');
    expect(service.toasts()[0]).toMatchObject({
      kind: 'success',
      title: 'All good!',
      text: 'Saved.',
      ttl: 4500,
    });

    vi.advanceTimersByTime(4500);
    expect(service.toasts()).toEqual([]);
  });

  it('pauses the countdown while a toast is hovered', () => {
    vi.useFakeTimers();
    const service = new ToastService();

    service.success('Saved.');
    const toast = service.toasts()[0];
    vi.advanceTimersByTime(2000);
    service.pause(toast);
    vi.advanceTimersByTime(5000);
    expect(service.toasts()).toContain(toast);

    service.resume(toast);
    vi.advanceTimersByTime(2499);
    expect(service.toasts()).toContain(toast);
    vi.advanceTimersByTime(1);
    expect(service.toasts()).not.toContain(toast);
  });
});

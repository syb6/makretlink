import { afterEach, describe, expect, it, vi } from 'vitest';
import { ApiError, ApiService } from './api.service';
import { ToastService } from './toast.service';

function api(): { service: ApiService; toast: ToastService } {
  const toast = { fromValidation: vi.fn() } as unknown as ToastService;
  return { service: new ApiService(toast), toast };
}

afterEach(() => {
  vi.unstubAllGlobals();
});

describe('ApiError', () => {
  it('preserves validation errors and HTTP status', () => {
    const error = new ApiError('Validation failed.', 422, {
      email: ['Enter a valid email address.'],
    });

    expect(error.message).toBe('Validation failed.');
    expect(error.status).toBe(422);
    expect(error.validation['email']).toEqual(['Enter a valid email address.']);
    expect(error.displayMessage).toBe('Enter a valid email address.');
  });
});

describe('ApiService', () => {
  it('preserves validation errors from the JSON flash bridge', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(new Response(JSON.stringify({
        message: 'Validation failed.',
        validation: { email: ['Enter a valid email address.'] },
      }), {
        status: 422,
        headers: { 'Content-Type': 'application/json' },
      })),
    );

    const { service } = api();
    await expect(service.get('/contact')).rejects.toMatchObject({
      status: 422,
      validation: { email: ['Enter a valid email address.'] },
    });
  });

  it('reports network errors and releases its global loading state', async () => {
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('offline')));
    const { service } = api();

    await expect(service.get('/home')).rejects.toMatchObject({
      status: 0,
      message: 'Unable to reach MarketLink. Check your connection and try again.',
    });
    expect(service.lastFailure()).toMatchObject({ status: 0 });
    expect(service.activeRequests()).toBe(0);
  });

  it('reports malformed success responses instead of returning null data', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('<html>error</html>', {
      status: 200,
      headers: { 'Content-Type': 'text/html' },
    })));
    const { service } = api();

    await expect(service.get('/home')).rejects.toMatchObject({
      status: 200,
      message: 'The server returned an unreadable response. Please try again.',
    });
  });

  it('preserves session-expired handling when the server returns an HTML error page', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('<html>expired</html>', {
      status: 419,
      headers: { 'Content-Type': 'text/html' },
    })));
    const { service } = api();

    await expect(service.get('/home')).rejects.toMatchObject({
      status: 419,
      message: 'Your session expired. Refresh the page and try again.',
    });
  });

  it('does not show duplicate error notifications when a page also handles the rejection', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify({ message: 'Unavailable.' }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' },
    })));
    const toast = new ToastService();
    const show = vi.spyOn(toast, 'show');
    const service = new ApiService(toast);

    try {
      await service.get('/home');
    } catch (error) {
      toast.fromValidation(error);
    }

    expect(show).not.toHaveBeenCalled();
    expect(service.lastFailure()).toMatchObject({ status: 503 });
  });

  it('clears a persistent request failure after recovery', async () => {
    vi.stubGlobal('fetch', vi.fn()
      .mockRejectedValueOnce(new TypeError('offline'))
      .mockResolvedValueOnce(new Response(JSON.stringify({ ok: true }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      })));
    const { service } = api();

    await expect(service.get('/home')).rejects.toBeInstanceOf(ApiError);
    service.clearFailure();
    await expect(service.get('/home')).resolves.toEqual({ ok: true });
    expect(service.lastFailure()).toBeNull();
  });

  it('accepts an empty 204 response', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(null, { status: 204 })));
    const { service } = api();

    await expect(service.del('/notifications/read-all')).resolves.toBeNull();
  });

  it('accepts valid top-level array responses', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify([
      { id: 1, available: 3 },
    ]), {
      status: 200,
      headers: { 'Content-Type': 'application/json' },
    })));
    const { service } = api();

    await expect(service.get<Array<{ id: number; available: number }>>(
      '/product-stock-options/1',
    )).resolves.toEqual([{ id: 1, available: 3 }]);
  });

  it('shows validation errors as toasts without a persistent connectivity banner', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify({
      message: 'Validation failed.',
      errors: { name: ['The name field is required.'] },
    }), {
      status: 422,
      headers: { 'Content-Type': 'application/json' },
    })));
    const { service, toast } = api();

    await expect(service.post('/contact', {})).rejects.toMatchObject({ status: 422 });
    expect(toast.fromValidation).toHaveBeenCalledOnce();
    expect(service.lastFailure()).toBeNull();
  });
});

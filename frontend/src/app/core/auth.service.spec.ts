import { TestBed } from '@angular/core/testing';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { ApiService } from './api.service';
import { AuthService } from './auth.service';
import { ToastService } from './toast.service';

afterEach(() => {
  TestBed.resetTestingModule();
});

describe('AuthService', () => {
  it('shares one in-flight session bootstrap request', async () => {
    let resolveRequest!: (value: { user: null; unread: number }) => void;
    const pending = new Promise<{ user: null; unread: number }>((resolve) => {
      resolveRequest = resolve;
    });
    const api = { get: vi.fn(() => pending) };

    TestBed.configureTestingModule({
      providers: [
        AuthService,
        { provide: ApiService, useValue: api },
        { provide: ToastService, useValue: { success: vi.fn() } },
      ],
    });

    const auth = TestBed.inject(AuthService);
    const firstLoad = auth.load();
    const secondLoad = auth.load();

    expect(api.get).toHaveBeenCalledOnce();
    expect(auth.loading()).toBe(true);

    resolveRequest({ user: null, unread: 0 });
    await expect(Promise.all([firstLoad, secondLoad])).resolves.toEqual([null, null]);
    expect(auth.loading()).toBe(false);
  });
});

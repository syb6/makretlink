import { Injectable, signal } from '@angular/core';
import { ToastService } from './toast.service';

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public validation: Record<string, string[]> = {},
    public flash: Record<string, string> = {},
    public reported = false,
  ) {
    super(message);
    this.name = 'ApiError';
  }

  get displayMessage(): string {
    return Object.values(this.validation)[0]?.[0] ?? this.message;
  }
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function validationErrors(value: unknown): Record<string, string[]> {
  if (!isRecord(value)) return {};

  return Object.fromEntries(
    Object.entries(value).flatMap(([field, messages]) => {
      const normalized = Array.isArray(messages)
        ? messages.filter((message): message is string => typeof message === 'string')
        : typeof messages === 'string' ? [messages] : [];
      return normalized.length ? [[field, normalized]] : [];
    }),
  );
}

function flashMessages(value: unknown): Record<string, string> {
  if (!isRecord(value)) return {};
  return Object.fromEntries(
    Object.entries(value).filter((entry): entry is [string, string] => typeof entry[1] === 'string'),
  );
}

/**
 * Minimal fetch wrapper for same-origin /api calls.
 * - Cookies flow automatically (same origin).
 * - CSRF: Laravel rotates XSRF-TOKEN cookie; we send it verbatim in the
 *   X-XSRF-TOKEN header on every write (read per request, after any session
 *   regeneration).
 */
@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly toast: ToastService;
  private activeRequestCount = 0;
  readonly activeRequests = signal(0);
  readonly lastFailure = signal<ApiError | null>(null);

  constructor(toast: ToastService) {
    this.toast = toast;
  }

  private xsrf(): string {
    const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return m ? decodeURIComponent(m[1]) : '';
  }

  private async request<T>(method: string, path: string, body?: unknown, asForm = false): Promise<T> {
    const headers: Record<string, string> = { Accept: 'application/json' };
    if (method !== 'GET') headers['X-XSRF-TOKEN'] = this.xsrf();
    if (body !== undefined && !asForm && !(body instanceof FormData)) headers['Content-Type'] = 'application/json';

    this.activeRequestCount += 1;
    this.activeRequests.set(this.activeRequestCount);

    try {
      let res: Response;
      try {
        res = await fetch('/api' + path, {
          method,
          headers,
          body: body === undefined ? undefined : body instanceof FormData ? body : asForm ? undefined : JSON.stringify(body),
          credentials: 'same-origin',
          cache: 'no-store',
        });
      } catch {
        throw this.report(new ApiError('Unable to reach MarketLink. Check your connection and try again.', 0));
      }

      const text = res.status === 204 ? '' : await res.text();
      let data: Record<string, unknown> | unknown[] | null = null;

      if (text.trim()) {
        try {
          const parsed: unknown = JSON.parse(text);
          if (!isRecord(parsed) && !Array.isArray(parsed)) {
            throw new SyntaxError('Expected a JSON object or array response.');
          }
          data = parsed;
        } catch {
          if (res.ok) {
            throw this.report(new ApiError(
              'The server returned an unreadable response. Please try again.',
              res.status,
            ));
          }
        }
      } else if (res.ok && res.status !== 204) {
        throw this.report(new ApiError('The server returned an empty response. Please try again.', res.status));
      }

      const errorData = isRecord(data) ? data : null;
      if (!res.ok) {
        if (res.status === 419) {
          throw this.report(new ApiError('Your session expired. Refresh the page and try again.', 419));
        }
        if (res.status === 422) {
          throw this.report(new ApiError(
            typeof errorData?.['message'] === 'string' ? errorData['message'] : 'Validation failed. Please check the form.',
            422,
            validationErrors(errorData?.['errors'] ?? errorData?.['validation']),
            flashMessages(errorData?.['flash']),
          ));
        }
        if (res.status === 401) {
          throw this.report(new ApiError(
            typeof errorData?.['message'] === 'string' ? errorData['message'] : 'Please sign in to continue.',
            401,
          ));
        }
        throw this.report(new ApiError(
          typeof errorData?.['message'] === 'string' ? errorData['message'] : `The request failed (${res.status}). Please try again.`,
          res.status,
          validationErrors(errorData?.['errors'] ?? errorData?.['validation']),
          flashMessages(errorData?.['flash']),
        ));
      }

      if (res.status === 204) return null as T;
      return data as T;
    } catch (error) {
      if (error instanceof ApiError) {
        if (!error.reported) this.report(error);
        throw error;
      }

      const apiError = new ApiError('Something went wrong while contacting MarketLink. Please try again.', 0);
      this.report(apiError);
      throw apiError;
    } finally {
      this.activeRequestCount = Math.max(0, this.activeRequestCount - 1);
      this.activeRequests.set(this.activeRequestCount);
    }
  }

  private report(error: ApiError): ApiError {
    if (error.status === 0 || error.status === 419 || error.status >= 500) {
      this.lastFailure.set(error);
    } else {
      this.toast.fromValidation(error);
    }
    error.reported = true;
    return error;
  }

  clearFailure(): void {
    this.lastFailure.set(null);
  }

  get<T>(path: string): Promise<T> {
    return this.request<T>('GET', path);
  }

  post<T>(path: string, body?: unknown, asForm = false): Promise<T> {
    return this.request<T>('POST', path, body, asForm);
  }

  put<T>(path: string, body?: unknown, asForm = false): Promise<T> {
    return this.request<T>('PUT', path, body, asForm);
  }

  del<T>(path: string, body?: unknown, asForm = false): Promise<T> {
    return this.request<T>('DELETE', path, body, asForm);
  }

  /** application/x-www-form-urlencoded style POST (matches Blade rule sets). */
  form<T>(path: string, fields: Record<string, unknown> = {}): Promise<T> {
    const fd = new FormData();
    for (const [k, v] of Object.entries(fields)) {
      if (v === undefined || v === null) continue;
      if (Array.isArray(v)) v.forEach((x) => fd.append(k + '[]', String(x)));
      else fd.append(k, String(v));
    }
    return this.request<T>('POST', path, fd, true);
  }

  /** multipart form-data POST (file uploads) */
  upload<T>(path: string, fields: Record<string, unknown>, files: Record<string, File | null>): Promise<T> {
    const fd = new FormData();
    for (const [k, v] of Object.entries(fields)) {
      if (v === undefined || v === null) continue;
      if (Array.isArray(v)) v.forEach((x) => fd.append(k + '[]', String(x)));
      else fd.append(k, String(v));
    }
    for (const [k, f] of Object.entries(files)) {
      if (f) fd.append(k, f);
    }
    return this.request<T>('POST', path, fd, true);
  }
}

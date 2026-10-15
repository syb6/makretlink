import { Injectable, computed, inject, signal } from '@angular/core';
import { ApiService } from './api.service';
import { ToastService } from './toast.service';

export interface CurrentUser {
  id: number;
  name: string;
  email: string;
  phone: string;
  role: 'admin' | 'farmer' | 'customer';
  status: string;
  avatar: string | null;
  customer_profile: { id: number; address: string; profile_image: string | null; cart_count: number } | null;
  farmer_profile: {
    id: number;
    business_name: string;
    contact_person: string;
    address: string;
    description: string | null;
    profile_image: string | null;
    approval_status: string;
  } | null;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private api = inject(ApiService);
  private toast = inject(ToastService);

  readonly user = signal<CurrentUser | null>(null);
  readonly unread = signal(0);
  readonly loading = signal(true);
  private loadRequest: Promise<CurrentUser | null> | null = null;

  readonly isAuthenticated = computed(() => !!this.user());
  readonly isCustomer = computed(() => this.user()?.role === 'customer');
  readonly isFarmer = computed(() => this.user()?.role === 'farmer' && this.user()?.farmer_profile?.approval_status === 'approved');
  readonly isAdmin = computed(() => this.user()?.role === 'admin');
  readonly cartCount = computed(() => this.user()?.customer_profile?.cart_count ?? 0);

  /** Bootstrap once per page load; safe to call after mutations. */
  load(): Promise<CurrentUser | null> {
    if (this.loadRequest) return this.loadRequest;

    this.loading.set(true);
    const request = (async (): Promise<CurrentUser | null> => {
      try {
        const data = await this.api.get<{ user: CurrentUser | null; unread: number }>('/me');
        this.user.set(data.user);
        this.unread.set(data.unread ?? 0);
        return data.user;
      } catch {
        this.user.set(null);
        return null;
      } finally {
        this.loading.set(false);
      }
    })();
    this.loadRequest = request;

    return request.finally(() => {
      if (this.loadRequest === request) this.loadRequest = null;
    });
  }

  async login(email: string, password: string, remember = false): Promise<{ redirect: string } | null> {
    const res = await this.api.form<{ ok: boolean; flash: Record<string, string>; redirect: string }>(
      '/auth/login', { email, password, remember: remember ? 1 : '' },
    );
    await this.load();
    return res;
  }

  async register(fields: Record<string, unknown>): Promise<{ redirect: string } | null> {
    const res = await this.api.form<{ ok: boolean; flash: Record<string, string>; redirect: string }>(
      '/auth/register', fields,
    );
    await this.load();
    return res;
  }

  async logout(): Promise<void> {
    await this.api.post('/auth/logout');
    this.user.set(null);
    this.unread.set(0);
    this.toast.success('You have been logged out.');
  }
}

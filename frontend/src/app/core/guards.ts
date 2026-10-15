import { inject } from '@angular/core';
import { Router, type CanActivateFn } from '@angular/router';
import { AuthService } from './auth.service';

export const authGuard: CanActivateFn = async () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (!auth.loading()) {
    return auth.isAuthenticated() ? true : router.parseUrl('/login');
  }
  const user = await auth.load();
  return user ? true : router.parseUrl('/login');
};

export function roleGuard(...roles: string[]): CanActivateFn {
  return async () => {
    const auth = inject(AuthService);
    const router = inject(Router);

    const user = auth.loading() ? await auth.load() : auth.user();
    if (!user) return router.parseUrl('/login');
    if (!roles.includes(user.role)) return router.parseUrl('/');

    // Pending/rejected farmers can't enter the farmer area.
    if (roles.includes('farmer') && user.farmer_profile?.approval_status !== 'approved') {
      return router.parseUrl('/');
    }

    return true;
  };
};

export const guestGuard: CanActivateFn = async () => {
  const auth = inject(AuthService);
  if (!auth.loading()) return auth.isAuthenticated() ? routerHome(inject(Router)) : true;
  const user = await auth.load();
  if (!user) return true;
  return routerHome(inject(Router));
};

function routerHome(router: Router) {
  const auth = inject(AuthService);
  const u = auth.user();
  const home = u?.role === 'admin' ? '/admin/dashboard' : u?.role === 'farmer' ? '/farmer/dashboard' : '/customer/dashboard';
  return router.parseUrl(home);
}

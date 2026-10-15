import { type Routes } from '@angular/router';
import { authGuard, guestGuard, roleGuard } from './core/guards';

const l = (p: string, c: string) =>
  import(/* @vite-ignore */ `./pages/${p}.ts`).then((m: any) => m[c]);

export const routes: Routes = [
  // Public
  { path: '', pathMatch: 'full', loadComponent: () => l('home.component', 'HomeComponent') },
  { path: 'markets', loadComponent: () => l('markets.component', 'MarketsComponent') },
  { path: 'markets/:id', loadComponent: () => l('market-detail.component', 'MarketDetailComponent') },
  { path: 'farmers', loadComponent: () => l('farmers.component', 'FarmersComponent') },
  { path: 'farmers/:id', loadComponent: () => l('farmer-detail.component', 'FarmerDetailComponent') },
  { path: 'products', loadComponent: () => l('products.component', 'ProductsComponent') },
  { path: 'products/:id', loadComponent: () => l('product-detail.component', 'ProductDetailComponent') },
  { path: 'about', loadComponent: () => l('about.component', 'AboutComponent') },
  { path: 'contact', loadComponent: () => l('contact.component', 'ContactComponent') },

  // Notifications (any signed-in role)
  { path: 'notifications', canActivate: [authGuard], loadComponent: () => l('notifications.component', 'NotificationsComponent') },

  // Auth
  { path: 'login', canActivate: [guestGuard], loadComponent: () => l('auth/login.component', 'LoginComponent') },
  { path: 'register', canActivate: [guestGuard], loadComponent: () => l('auth/register.component', 'RegisterComponent') },
  { path: 'forgot-password', canActivate: [guestGuard], loadComponent: () => l('auth/forgot-password.component', 'ForgotPasswordComponent') },
  { path: 'reset-password/:token', loadComponent: () => l('auth/reset-password.component', 'ResetPasswordComponent') },

  // Customer
  {
    path: 'customer',
    canActivate: [roleGuard('customer')],
    loadComponent: () => l('customer/customer.layout', 'CustomerLayoutComponent'),
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
      { path: 'dashboard', loadComponent: () => l('customer/dashboard.component', 'CustomerDashboardComponent') },
      { path: 'profile', loadComponent: () => l('customer/profile.component', 'CustomerProfileComponent') },
      { path: 'cart', loadComponent: () => l('customer/cart.component', 'CartComponent') },
      { path: 'checkout', loadComponent: () => l('customer/checkout.component', 'CheckoutComponent') },
      { path: 'orders', loadComponent: () => l('customer/orders.component', 'CustomerOrdersComponent') },
      { path: 'orders/:id', loadComponent: () => l('customer/order-detail.component', 'CustomerOrderDetailComponent') },
      { path: 'favorites', loadComponent: () => l('customer/favorites.component', 'FavoritesComponent') },
    ],
  },

  // Farmer
  {
    path: 'farmer',
    canActivate: [roleGuard('farmer')],
    loadComponent: () => l('farmer/farmer.layout', 'FarmerLayoutComponent'),
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
      { path: 'dashboard', loadComponent: () => l('farmer/dashboard.component', 'FarmerDashboardComponent') },
      { path: 'profile', loadComponent: () => l('farmer/profile.component', 'FarmerProfileComponent') },
      { path: 'stalls', loadComponent: () => l('farmer/stalls.component', 'FarmerStallsComponent') },
      { path: 'products', loadComponent: () => l('farmer/products.component', 'FarmerProductsComponent') },
      { path: 'stock', loadComponent: () => l('farmer/stock.component', 'FarmerStockComponent') },
      { path: 'orders', loadComponent: () => l('farmer/orders.component', 'FarmerOrdersComponent') },
      { path: 'orders/:id', loadComponent: () => l('farmer/order-detail.component', 'FarmerOrderDetailComponent') },
      { path: 'reviews', loadComponent: () => l('farmer/reviews.component', 'FarmerReviewsComponent') },
      { path: 'slots', loadComponent: () => l('farmer/slots.component', 'FarmerSlotsComponent') },
    ],
  },

  // Admin
  {
    path: 'admin',
    canActivate: [roleGuard('admin')],
    loadComponent: () => l('admin/admin.layout', 'AdminLayoutComponent'),
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
      { path: 'dashboard', loadComponent: () => l('admin/dashboard.component', 'AdminDashboardComponent') },
      { path: 'profile', loadComponent: () => l('admin/profile.component', 'AdminProfileComponent') },
      { path: 'users', loadComponent: () => l('admin/users.component', 'AdminUsersComponent') },
      { path: 'markets', loadComponent: () => l('admin/markets.component', 'AdminMarketsComponent') },
      { path: 'categories', loadComponent: () => l('admin/categories.component', 'AdminCategoriesComponent') },
      { path: 'feedback', loadComponent: () => l('admin/feedback.component', 'AdminFeedbackComponent') },
      { path: 'moderation', loadComponent: () => l('admin/moderation.component', 'AdminModerationComponent') },
      { path: 'reports', loadComponent: () => l('admin/reports.component', 'AdminReportsComponent') },
      { path: 'announcements', loadComponent: () => l('admin/announcements.component', 'AdminAnnouncementsComponent') },
    ],
  },

  { path: '**', loadComponent: () => l('not-found.component', 'NotFoundComponent') },
];

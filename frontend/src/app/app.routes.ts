import { Routes } from '@angular/router';

export const routes: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./features/home/home-menu-page').then((m) => m.HomeMenuPage),
  },
  {
    path: 'plataformas',
    loadComponent: () =>
      import('./features/catalog/catalog-page').then((m) => m.CatalogPage),
  },
  {
    path: 'pago',
    loadComponent: () =>
      import('./features/payment/payment-page').then((m) => m.PaymentPage),
  },
  {
    path: 'dashboard',
    loadComponent: () =>
      import('./features/dashboard/dashboard-page').then((m) => m.DashboardPage),
  },
  {
    path: 'netcode/codigos',
    loadComponent: () =>
      import('./features/netcode/netcode-codes-page').then((m) => m.NetcodeCodesPage),
  },
  {
    path: 'netcode/inicio-sesion',
    loadComponent: () =>
      import('./features/netcode/netcode-access-page').then((m) => m.NetcodeAccessPage),
  },
  {
    path: '**',
    redirectTo: '',
  },
];

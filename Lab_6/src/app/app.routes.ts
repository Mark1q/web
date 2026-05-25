import { Routes } from '@angular/router';

export const routes: Routes = [
  { path: '', redirectTo: 'browse', pathMatch: 'full' },
  { path: 'browse',   loadComponent: () => import('./components/browse/browse.component').then(m => m.BrowseComponent) },
  { path: 'add',      loadComponent: () => import('./components/add-book/add-book.component').then(m => m.AddBookComponent) },
  { path: 'manage',   loadComponent: () => import('./components/manage/manage.component').then(m => m.ManageComponent) },
  { path: 'lendings', loadComponent: () => import('./components/lendings/lendings.component').then(m => m.LendingsComponent) },
  { path: 'genres',   loadComponent: () => import('./components/genres/genres.component').then(m => m.GenresComponent) },
  { path: '**', redirectTo: 'browse' }
];

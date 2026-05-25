import { Component } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';

@Component({
  selector: 'app-header',
  standalone: true,
  imports: [RouterLink, RouterLinkActive],
  template: `
    <header>
      <div class="header-inner">
        <a class="logo" routerLink="/browse">📚 Book<span>Shelf</span></a>
        <nav>
          <a routerLink="/browse"   routerLinkActive="active">Browse</a>
          <a routerLink="/add"      routerLinkActive="active">Add Book</a>
          <a routerLink="/manage"   routerLinkActive="active">Manage</a>
          <a routerLink="/lendings" routerLinkActive="active">Lendings</a>
          <a routerLink="/genres"   routerLinkActive="active">Genres</a>
        </nav>
      </div>
    </header>
  `,
  styles: [`
    header {
      background: var(--ink);
      color: var(--paper);
      padding: 0 2rem;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 12px rgba(0,0,0,0.4);
    }
    .header-inner {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 64px;
      gap: 2rem;
    }
    .logo {
      font-family: 'Playfair Display', serif;
      font-size: 1.5rem;
      font-weight: 900;
      color: var(--gold);
      text-decoration: none;
      letter-spacing: -0.02em;
      white-space: nowrap;
    }
    .logo span { color: var(--paper); font-weight: 400; font-style: italic; }
    nav { display: flex; gap: 0.25rem; align-items: center; }
    nav a {
      color: var(--parchment);
      text-decoration: none;
      padding: 0.4rem 0.85rem;
      border-radius: var(--radius);
      font-size: 0.95rem;
      font-family: 'Crimson Pro', serif;
      letter-spacing: 0.02em;
      transition: all var(--transition);
      white-space: nowrap;
    }
    nav a:hover { background: rgba(201,151,58,0.2); color: var(--gold-light); }
    nav a.active { background: var(--gold); color: var(--ink); font-weight: 600; }
    @media (max-width: 768px) {
      .header-inner { flex-wrap: wrap; height: auto; padding: 0.75rem 0; }
      nav { flex-wrap: wrap; }
    }
  `]
})
export class HeaderComponent {}

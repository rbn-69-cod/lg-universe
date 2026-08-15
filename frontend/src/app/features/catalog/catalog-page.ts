import { DecimalPipe } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import {
  LucideBolt,
  LucideCrown,
  LucideFilm,
  LucideMinus,
  LucidePlus,
  LucideSearch,
  LucideShoppingCart,
  LucideTrash2,
  LucideTv,
  LucideX,
} from '@lucide/angular';

import { PlataformaCatalogItem } from '../../core/api-types';
import { CatalogApi } from '../../core/catalog-api';
import { CartStore } from './cart-store';

@Component({
  selector: 'app-catalog-page',
  imports: [
    DecimalPipe,
    FormsModule,
    LucideBolt,
    LucideCrown,
    LucideFilm,
    LucideMinus,
    LucidePlus,
    LucideSearch,
    LucideShoppingCart,
    LucideTrash2,
    LucideTv,
    LucideX,
  ],
  templateUrl: './catalog-page.html',
  styleUrl: './catalog-page.css',
})
export class CatalogPage {
  private readonly catalogApi = inject(CatalogApi);
  readonly cart = inject(CartStore);

  readonly platforms = signal<PlataformaCatalogItem[]>([]);
  readonly searchTerm = signal('');
  readonly loading = signal(true);
  readonly error = signal('');
  readonly toast = signal('');
  readonly isCartOpen = signal(false);

  readonly visiblePlatforms = computed(() => {
    const term = this.searchTerm().trim().toLowerCase();
    return this.platforms().filter((platform) =>
      platform.nombre.toLowerCase().includes(term),
    );
  });

  constructor() {
    this.catalogApi.getPlataformas().subscribe({
      next: (platforms) => {
        this.platforms.set(platforms);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('No se pudo cargar el catalogo.');
        this.loading.set(false);
      },
    });
  }

  addToCart(platform: PlataformaCatalogItem): void {
    this.cart.add(platform);
    this.showToast(`${platform.nombre} agregado`);
  }

  buyNow(platform: PlataformaCatalogItem): void {
    this.cart.add(platform);
    this.cart.checkout();
  }

  checkout(): void {
    if (!this.cart.checkout()) {
      this.showToast('Agrega algo al carrito');
    }
  }

  imageFallback(event: Event): void {
    const img = event.target as HTMLImageElement;
    img.style.display = 'none';
    img.closest('.image-box')?.classList.add('image-fallback');
  }

  private showToast(message: string): void {
    this.toast.set(message);
    window.setTimeout(() => this.toast.set(''), 1800);
  }
}

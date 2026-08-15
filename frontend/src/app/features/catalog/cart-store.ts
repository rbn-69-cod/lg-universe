import { Injectable, computed, signal } from '@angular/core';

import { CartItem, CheckoutPayload, PlataformaCatalogItem } from '../../core/api-types';

const CART_KEY = 'ig_cart_pro';
const PAYLOAD_KEY = 'checkout_payload';

@Injectable({ providedIn: 'root' })
export class CartStore {
  readonly items = signal<CartItem[]>(this.readCart());
  readonly itemCount = computed(() =>
    this.items().reduce((total, item) => total + item.quantity, 0),
  );
  readonly subtotal = computed(() =>
    this.items().reduce((total, item) => total + item.price * item.quantity, 0),
  );

  add(platform: PlataformaCatalogItem): void {
    const current = [...this.items()];
    const existing = current.find((item) => item.id === platform.id);

    if (existing) {
      existing.quantity += 1;
    } else {
      current.push({
        id: platform.id,
        name: platform.nombre,
        price: Number(platform.precio),
        quantity: 1,
      });
    }

    this.write(current);
  }

  increment(index: number): void {
    const current = [...this.items()];
    if (!current[index]) return;
    current[index] = { ...current[index], quantity: current[index].quantity + 1 };
    this.write(current);
  }

  decrement(index: number): void {
    const current = [...this.items()];
    if (!current[index]) return;

    if (current[index].quantity <= 1) {
      current.splice(index, 1);
    } else {
      current[index] = { ...current[index], quantity: current[index].quantity - 1 };
    }

    this.write(current);
  }

  remove(index: number): void {
    const current = [...this.items()];
    current.splice(index, 1);
    this.write(current);
  }

  checkout(): boolean {
    if (this.items().length === 0) return false;

    const payload: CheckoutPayload = {
      items: this.items(),
      total: this.subtotal(),
      timestamp: new Date().toISOString(),
      currency: 'PEN',
    };

    sessionStorage.setItem(PAYLOAD_KEY, JSON.stringify(payload));
    window.location.href = '/pago';

    return true;
  }

  private write(items: CartItem[]): void {
    this.items.set(items);
    localStorage.setItem(CART_KEY, JSON.stringify(items));
  }

  private readCart(): CartItem[] {
    try {
      const raw = localStorage.getItem(CART_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch {
      return [];
    }
  }
}

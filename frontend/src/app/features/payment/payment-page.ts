import { Component, computed, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import {
  LucideArrowLeft,
  LucideBadgeCheck,
  LucideBolt,
  LucideCopy,
  LucideInfo,
  LucideQrCode,
  LucideReceipt,
  LucideShield,
  LucideShoppingBag,
} from '@lucide/angular';

import { CartItem, CheckoutPayload } from '../../core/api-types';

const CART_KEY = 'ig_cart_pro';
const PAYLOAD_KEY = 'checkout_payload';

interface PaymentMethod {
  id: number;
  title: string;
  subtitle: string;
  badge: string;
  recommended: boolean;
  qrSrc: string;
  qrFallback: string;
  accountName: string;
  accountPhone: string;
  copyPhone: string;
  whatsapp: string;
  active: boolean;
}

interface PaymentSettingsResponse {
  data: {
    seller: {
      business_name: string;
      display_name: string;
      contact_name: string;
      whatsapp: string;
      phone: string;
      email: string;
      address: string;
      support_text: string;
    };
    instructions: string;
    methods: Array<{
      id: number;
      title: string;
      subtitle: string;
      badge: string;
      recommended: boolean;
      qr_src: string;
      qr_fallback: string;
      account_name: string;
      account_phone: string;
      copy_phone: string;
      whatsapp: string;
      active: boolean;
    }>;
  };
}

@Component({
  selector: 'app-payment-page',
  imports: [
    LucideArrowLeft,
    LucideBadgeCheck,
    LucideBolt,
    LucideCopy,
    LucideInfo,
    LucideQrCode,
    LucideReceipt,
    LucideShield,
    LucideShoppingBag,
  ],
  templateUrl: './payment-page.html',
  styleUrl: './payment-page.css',
})
export class PaymentPage {
  private readonly http = inject(HttpClient);

  readonly cart = signal<CartItem[]>(this.loadCart());
  readonly toast = signal('');
  readonly seller = signal({
    businessName: 'IG UNIVERSE',
    displayName: 'IG UNIVERSE',
    contactName: 'Igarlos R Mamani Q',
    whatsapp: '51954850003',
    phone: '954850003',
    email: '',
    address: '',
    supportText: 'Finaliza tu compra y envia el comprobante.',
  });
  readonly instructions = signal('1) Escanea QR o transfiere al numero - 2) Paga el monto exacto - 3) Envia el comprobante por WhatsApp - 4) Te activamos rapido.');

  readonly subtotal = computed(() =>
    this.cart().reduce((total, item) => total + Number(item.price || 0) * Number(item.quantity || 1), 0),
  );

  readonly itemCount = computed(() =>
    this.cart().reduce((total, item) => total + Number(item.quantity || 1), 0),
  );

  readonly methods = signal<PaymentMethod[]>([
    {
      id: 1,
      title: 'Yape / Plin',
      subtitle: 'Opcion 1',
      badge: 'recomendado',
      recommended: true,
      qrSrc: '/images/qr-yape.jpeg',
      qrFallback:
        'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=Yape:954850003&color=4A6FFF&bgcolor=ffffff',
      accountName: 'Igarlos R Mamani Q',
      accountPhone: '954850003',
      copyPhone: '907978279',
      whatsapp: '51954850003',
      active: true,
    },
    {
      id: 2,
      title: 'Yape / Plin',
      subtitle: 'Opcion 2',
      badge: '',
      recommended: false,
      qrSrc: '/images/qr-yape-2.jpeg',
      qrFallback:
        'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=Yape:968238516&color=4A6FFF&bgcolor=ffffff',
      accountName: 'Jennifer N Gallegos Q',
      accountPhone: '968238516',
      copyPhone: '968238516',
      whatsapp: '51968238516',
      active: true,
    },
  ]);

  constructor() {
    this.loadPaymentSettings();
  }

  money(value: number): string {
    return `S/ ${Number(value || 0).toFixed(2)}`;
  }

  lineTotal(item: CartItem): number {
    return Number(item.price || 0) * Number(item.quantity || 1);
  }

  copyPhone(phone: string): void {
    this.copyToClipboard(phone);
  }

  sendReceipt(method: PaymentMethod): void {
    if (this.cart().length === 0) {
      this.showToast('Carrito vacio');
      return;
    }

    const message = this.buildWhatsAppMessage(method);
    const url = `https://wa.me/${method.whatsapp || this.seller().whatsapp}?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
    this.showToast('Abriendo WhatsApp...');
  }

  qrFallback(event: Event, method: PaymentMethod): void {
    const img = event.target as HTMLImageElement;
    if (img.src !== method.qrFallback) {
      img.src = method.qrFallback;
    }
  }

  private loadCart(): CartItem[] {
    try {
      const rawPayload = sessionStorage.getItem(PAYLOAD_KEY);
      if (rawPayload) {
        const payload = JSON.parse(rawPayload) as CheckoutPayload;
        if (payload && Array.isArray(payload.items)) {
          return payload.items;
        }
      }
    } catch {
      // Keep the same fallback behavior as the Blade screen.
    }

    try {
      const rawCart = localStorage.getItem(CART_KEY);
      return rawCart ? JSON.parse(rawCart) : [];
    } catch {
      return [];
    }
  }

  private buildWhatsAppMessage(method: PaymentMethod): string {
    let message = `Hola ${this.seller().displayName || 'IG UNIVERSE'}, ya realice el pago\n\n`;
    message += '*RESUMEN DE COMPRA:*\n';
    message += '--------------------\n';

    this.cart().forEach((item, index) => {
      message += `${index + 1}. ${item.name}\n`;
      message += `   Cantidad: ${Number(item.quantity || 1)}\n`;
      message += `   Precio: ${this.money(Number(item.price || 0))}\n\n`;
    });

    message += '--------------------\n';
    message += `*TOTAL PAGADO:* ${this.money(this.subtotal())}\n`;
    message += `*Cuenta destino:* ${method.accountName} (${method.accountPhone})\n`;
    message += '--------------------\n\n';
    message += 'Adjunto el comprobante de pago.';

    return message;
  }

  private loadPaymentSettings(): void {
    this.http.get<PaymentSettingsResponse>('/api/v1/payment-settings', {
      headers: { Accept: 'application/json' },
    }).subscribe({
      next: (response) => {
        const data = response.data;
        this.seller.set({
          businessName: data.seller.business_name,
          displayName: data.seller.display_name,
          contactName: data.seller.contact_name,
          whatsapp: data.seller.whatsapp,
          phone: data.seller.phone,
          email: data.seller.email,
          address: data.seller.address,
          supportText: data.seller.support_text,
        });
        this.instructions.set(data.instructions);
        this.methods.set(data.methods.map((method) => ({
          id: method.id,
          title: method.title,
          subtitle: method.subtitle,
          badge: method.badge,
          recommended: method.recommended,
          qrSrc: method.qr_src,
          qrFallback: method.qr_fallback,
          accountName: method.account_name,
          accountPhone: method.account_phone,
          copyPhone: method.copy_phone,
          whatsapp: method.whatsapp,
          active: method.active,
        })));
      },
      error: () => {
        // Conserva los metodos actuales si la API aun no esta disponible.
      },
    });
  }

  private async copyToClipboard(text: string): Promise<void> {
    try {
      await navigator.clipboard.writeText(text);
      this.showToast(`Copiado: ${text}`);
    } catch {
      const textarea = document.createElement('textarea');
      textarea.value = text;
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      textarea.remove();
      this.showToast(`Copiado: ${text}`);
    }
  }

  private showToast(message: string): void {
    this.toast.set(message);
    window.setTimeout(() => this.toast.set(''), 1600);
  }
}

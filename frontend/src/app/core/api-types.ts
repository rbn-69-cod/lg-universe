export interface ApiCollection<T> {
  data: T[];
}

export interface PlataformaCatalogItem {
  id: number;
  nombre: string;
  imagen: string | null;
  precio: number;
  descripcion: string | null;
  features: string[];
  activacion: string | null;
  terminos: string | null;
  activo: boolean;
  orden: number | null;
}

export interface CartItem {
  id: number;
  name: string;
  price: number;
  quantity: number;
}

export interface CheckoutPayload {
  items: CartItem[];
  total: number;
  timestamp: string;
  currency: 'PEN';
}

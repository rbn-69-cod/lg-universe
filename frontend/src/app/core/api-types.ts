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
  duraciones: PlataformaCatalogDuration[];
}

export interface PlataformaCatalogDuration {
  id: number | null;
  duracion_meses: number;
  precio: number;
  activo: boolean;
}

export interface CartItem {
  id: string;
  platform_id: number;
  name: string;
  duration_months: number;
  price: number;
  quantity: number;
}

export interface CheckoutPayload {
  items: CartItem[];
  total: number;
  timestamp: string;
  currency: 'PEN';
}

import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';

export interface DashboardStats {
  cuentas: number;
  perfiles: number;
  ocupados: number;
  disponibles: number;
  vencidos: number;
  capacidad_cuentas: number;
  capacidad_perfiles: number;
}

export interface DashboardProfile {
  id: number;
  nombre_perfil: string | null;
  pin: string | null;
  numero: string | null;
  numero_tipo: 'peru' | 'internacional' | 'sin_numero';
  vendedor: string | null;
  costo: string | number | null;
  fecha_inicio: string | null;
  fecha_fin: string | null;
  estado_excel: string | null;
  ocupado: boolean;
  source_platforma: string | null;
  source_hoja_excel: string | null;
  source_row: number | null;
  cliente_acceso_usuario: string | null;
  cuenta?: {
    email: string | null;
    password: string | null;
    cliente_acceso_usuario: string | null;
    bot_preferencia: BotPreference;
    bot_hogar_url: string | null;
    bot_temporal_url: string | null;
    bot_acceso4_url: string | null;
  };
}

export interface DashboardAccount {
  id: number;
  email: string | null;
  password: string | null;
  source_platforma: string | null;
  perfiles_total: number | null;
  perfiles_usados: number | null;
  activo: boolean;
  source_hoja_excel: string | null;
  source_row: number | null;
  cliente_acceso_usuario: string | null;
  bot_preferencia: BotPreference;
  bot_hogar_url: string | null;
  bot_temporal_url: string | null;
  bot_acceso4_url: string | null;
  perfiles: DashboardProfile[];
}

export interface DashboardRange {
  id: number;
  plataforma: string;
  nombre_tabla: string | null;
  producto_slug: string | null;
  hoja_excel: string;
  fila_inicio: number;
  fila_fin: number;
  archivo_url: string | null;
  bot_codigo_url: string | null;
  bot_soporte_url: string | null;
  bot_codigo_masked_url: string | null;
  bot_soporte_masked_url: string | null;
  columna_perfil: string;
  columna_pin: string;
  columna_numero: string;
  columna_vendedor_igarlos: string;
  columna_vendedor_nikol: string;
  columna_costo: string;
  columna_fecha_inicio: string;
  columna_fecha_fin: string;
  columna_estado: string;
  columna_correo: string;
  columna_password: string;
  columna_cliente_acceso_usuario: string;
  activo: boolean;
  ultimo_sync_at: string | null;
  ultimo_error: string | null;
}

export interface DashboardRangePayload {
  plataforma: string;
  nombre_tabla: string;
  producto_slug: string;
  archivo_url: string;
  bot_codigo_url: string;
  bot_soporte_url: string;
  hoja_excel: string;
  fila_inicio: number;
  fila_fin: number;
  columna_perfil: string;
  columna_pin: string;
  columna_numero: string;
  columna_vendedor_igarlos: string;
  columna_vendedor_nikol: string;
  columna_costo: string;
  columna_fecha_inicio: string;
  columna_fecha_fin: string;
  columna_estado: string;
  columna_correo: string;
  columna_password: string;
  columna_cliente_acceso_usuario: string;
  activo: boolean;
}

export interface DashboardImapItem {
  id: number;
  message_id: string | null;
  thread_id: string | null;
  imap_uid: string | null;
  destinatario_original: string;
  remitente: string | null;
  asunto: string;
  tipo: 'login_code' | 'household_update' | 'temporary_access' | 'codigo_4' | 'link' | 'codigo' | 'sin_dato' | 'unknown';
  tipo_label: string;
  valor_extraido: string;
  codigo: string | null;
  action_url: string | null;
  has_client_value: boolean;
  client_visible: boolean;
  dashboard_state: 'visible_cliente' | 'solo_dashboard_expirado' | 'solo_dashboard_revision';
  dashboard_state_label: string;
  dashboard_state_tone: 'ok' | 'warn' | 'danger';
  extraction_status: string;
  found_links: string[];
  fecha_recibido: string | null;
  fecha_procesado_db: string | null;
  validity_start_at: string | null;
  expires_at: string | null;
  seconds_remaining: number;
  dashboard_age_seconds: number;
  raw_email: string | null;
  html_body_original: string | null;
  text_body_original: string | null;
}

export interface DashboardImapStatus {
  configured: boolean;
  mailbox_raw: string | null;
  mailbox: string | null;
  username: string | null;
  search_criteria: string;
  mark_seen: boolean;
  retention_minutes: number;
  history_window_hours: number;
  cron_url: string | null;
  cron_url_masked: string | null;
  cron_token_masked: string | null;
  stored_count: number;
  stored_recent_count: number;
  client_visible_count: number;
  dashboard_only_count: number;
  last_processed_at: string | null;
  recent_items: DashboardImapItem[];
  client_visible_items: DashboardImapItem[];
  dashboard_only_items: DashboardImapItem[];
}

export interface DashboardTutorial {
  key: string;
  title: string;
  steps: string[];
  media_url: string | null;
  media_type: 'image' | 'video' | null;
  media_path: string | null;
  updated_at: string | null;
}

export interface DashboardCatalogPlatform {
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
  duraciones: DashboardCatalogDuration[];
}

export interface DashboardCatalogDuration {
  id: number | null;
  duracion_meses: 1 | 2 | 3 | 6;
  precio: number;
  activo: boolean;
}

export interface DashboardCatalogPayload {
  nombre: string;
  imagen: string;
  precio: number;
  descripcion: string;
  features: string;
  activacion: string;
  terminos: string;
  activo: boolean;
  duraciones: DashboardCatalogDuration[];
}

export interface DashboardAdmin {
  id: number;
  name: string;
  email: string;
  created_at: string | null;
}

export interface DashboardAdminPayload {
  name: string;
  email: string;
  password: string;
}

export interface DashboardImapSettingsPayload {
  imap_server: string;
  imap_user: string;
  imap_password: string;
  imap_search_criteria: string;
  imap_mark_seen: boolean;
  emails_max_minutes: number;
  cron_token: string;
}

export interface DashboardPaymentSeller {
  business_name: string;
  display_name: string;
  contact_name: string;
  whatsapp: string;
  phone: string;
  email: string;
  address: string;
  support_text: string;
}

export interface DashboardPaymentMethod {
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
}

export interface DashboardPaymentSettings {
  seller: DashboardPaymentSeller;
  instructions: string;
  methods: DashboardPaymentMethod[];
}

export type BotPreference = 'principal' | 'personalizado';

export interface AccountBotLinksPayload {
  cliente_acceso_usuario: string;
  bot_preferencia: BotPreference;
  bot_hogar_url: string;
  bot_temporal_url: string;
  bot_acceso4_url: string;
}

export interface DashboardData {
  stats: DashboardStats;
  accounts: DashboardAccount[];
  profiles: DashboardProfile[];
  ranges: DashboardRange[];
  catalog: DashboardCatalogPlatform[];
  admins: DashboardAdmin[];
  imap: DashboardImapStatus;
  tutorials: Record<string, DashboardTutorial>;
  tutorial_labels: Record<string, string>;
  payment_settings: DashboardPaymentSettings;
  last_sync: string | null;
}

export interface DashboardResponse {
  data: DashboardData;
}

export interface DashboardSyncResponse extends DashboardResponse {
  message: string;
  stats: Record<string, number>;
}

export interface DashboardImapRunResponse extends DashboardResponse {
  message: string;
  output: string;
}

export interface DashboardImapTestResponse extends DashboardResponse {
  message: string;
  criteria: string;
  found: number;
  latest: Array<{
    uid: number;
    subject: string;
    from: string | null;
    date: string | null;
  }>;
}

@Injectable({ providedIn: 'root' })
export class DashboardApi {
  private readonly http = inject(HttpClient);

  get(): Observable<DashboardResponse> {
    return this.http.get<DashboardResponse>('/api/v1/dashboard', {
      headers: { Accept: 'application/json' },
    }).pipe(catchError((error) => this.handle(error)));
  }

  syncExcel(options?: { rangeId?: number; plataforma?: string }): Observable<DashboardSyncResponse> {
    const body = {
      ...(options?.rangeId ? { range_id: options.rangeId } : {}),
      ...(options?.plataforma ? { plataforma: options.plataforma } : {}),
    };

    return this.http.post<DashboardSyncResponse>('/api/v1/dashboard/excel-sync', body, {
      headers: {
        Accept: 'application/json',
        'X-XSRF-TOKEN': this.xsrfToken(),
      },
    }).pipe(catchError((error) => this.handle(error)));
  }

  runImap(): Observable<DashboardImapRunResponse> {
    return this.http.post<DashboardImapRunResponse>('/api/v1/dashboard/imap-run', {}, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  testImap(): Observable<DashboardImapTestResponse> {
    return this.http.post<DashboardImapTestResponse>('/api/v1/dashboard/imap-test', {}, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  updateImapSettings(payload: DashboardImapSettingsPayload): Observable<DashboardResponse> {
    return this.http.put<DashboardResponse>('/api/v1/dashboard/imap-settings', payload, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  updatePaymentSettings(payload: DashboardPaymentSettings): Observable<DashboardResponse> {
    return this.http.put<DashboardResponse>('/api/v1/dashboard/payment-settings', payload, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  uploadPaymentQr(methodId: number, file: File): Observable<DashboardResponse> {
    const body = new FormData();
    body.set('qr', file);

    return this.http.post<DashboardResponse>(`/api/v1/dashboard/payment-settings/${methodId}/qr`, body, {
      headers: {
        Accept: 'application/json',
        'X-XSRF-TOKEN': this.xsrfToken(),
      },
    }).pipe(catchError((error) => this.handle(error)));
  }

  updateTutorial(key: string, payload: { title: string; steps: string; media?: File | null }): Observable<DashboardResponse> {
    const body = new FormData();
    body.set('title', payload.title);
    body.set('steps', payload.steps);
    if (payload.media) body.set('media', payload.media);

    return this.http.post<DashboardResponse>(`/api/v1/dashboard/tutorials/${key}`, body, {
      headers: {
        Accept: 'application/json',
        'X-XSRF-TOKEN': this.xsrfToken(),
      },
    }).pipe(catchError((error) => this.handle(error)));
  }

  deleteTutorialMedia(key: string): Observable<DashboardResponse> {
    return this.http.delete<DashboardResponse>(`/api/v1/dashboard/tutorials/${key}/media`, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  createCatalogPlatform(payload: DashboardCatalogPayload): Observable<DashboardResponse> {
    return this.http.post<DashboardResponse>('/api/v1/dashboard/catalog', payload, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  updateCatalogPlatform(id: number, payload: DashboardCatalogPayload): Observable<DashboardResponse> {
    return this.http.put<DashboardResponse>(`/api/v1/dashboard/catalog/${id}`, payload, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  deleteCatalogPlatform(id: number): Observable<DashboardResponse> {
    return this.http.delete<DashboardResponse>(`/api/v1/dashboard/catalog/${id}`, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  moveCatalogPlatform(id: number, direction: 'up' | 'down'): Observable<DashboardResponse> {
    return this.http.patch<DashboardResponse>(`/api/v1/dashboard/catalog/${id}/${direction}`, {}, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  createAdmin(payload: DashboardAdminPayload): Observable<DashboardResponse> {
    return this.http.post<DashboardResponse>('/api/v1/dashboard/admins', payload, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  updateAdmin(id: number, payload: DashboardAdminPayload): Observable<DashboardResponse> {
    return this.http.put<DashboardResponse>(`/api/v1/dashboard/admins/${id}`, payload, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  deleteAdmin(id: number): Observable<DashboardResponse> {
    return this.http.delete<DashboardResponse>(`/api/v1/dashboard/admins/${id}`, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  clearImportedData(confirmation: string): Observable<DashboardResponse> {
    return this.http.post<DashboardResponse>('/api/v1/dashboard/imported-data/clear', { confirmation }, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  createRange(payload: DashboardRangePayload): Observable<DashboardResponse> {
    return this.http.post<DashboardResponse>('/api/v1/dashboard/excel-ranges', payload, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  updateRange(id: number, payload: DashboardRangePayload): Observable<DashboardResponse> {
    return this.http.put<DashboardResponse>(`/api/v1/dashboard/excel-ranges/${id}`, payload, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  deleteRange(id: number): Observable<DashboardResponse> {
    return this.http.delete<DashboardResponse>(`/api/v1/dashboard/excel-ranges/${id}`, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  updateAccountBotLinks(id: number, payload: AccountBotLinksPayload): Observable<DashboardResponse> {
    return this.http.put<DashboardResponse>(`/api/v1/dashboard/accounts/${id}/bot-links`, payload, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  clearImapDashboardOnlyHistory(): Observable<DashboardResponse & { deleted: number; message: string }> {
    return this.http.post<DashboardResponse & { deleted: number; message: string }>('/api/v1/dashboard/imap-history/clear', {}, {
      headers: this.writeHeaders(),
    }).pipe(catchError((error) => this.handle(error)));
  }

  logout(): Observable<unknown> {
    return this.http.get('/api/v1/dashboard/logout', {
      headers: {
        Accept: 'application/json',
      },
    });
  }

  private handle(error: HttpErrorResponse): Observable<never> {
    if (error.status === 401 || error.status === 419) {
      window.location.href = '/login';
    }

    return throwError(() => error);
  }

  private xsrfToken(): string {
    const cookie = document.cookie
      .split('; ')
      .find((item) => item.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : '';
  }

  private writeHeaders(): Record<string, string> {
    return {
      Accept: 'application/json',
      'X-XSRF-TOKEN': this.xsrfToken(),
    };
  }
}

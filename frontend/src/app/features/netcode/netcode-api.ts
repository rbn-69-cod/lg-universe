import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

export type NetcodeSearchSubject = 'hogar' | 'temporal' | 'acceso4';

export interface Tutorial {
  title: string;
  steps: string[];
  media_url: string | null;
  media_type: 'image' | 'video' | null;
}

export interface NetcodeSearchResponse {
  status: 'success' | 'not_found' | 'error';
  found?: boolean;
  message: string;
  type?: 'codigo' | 'link' | 'login';
  value?: string;
  email?: string;
  received_at?: string | null;
  valor_extraido?: string;
  tipo?: 'codigo' | 'link' | 'login';
  fecha?: string | null;
  processed_at?: string | null;
  expires_at?: string | null;
  seconds_remaining?: number;
  valid_for_minutes?: number;
  debug_id?: number;
  asunto_found?: string;
  account_id?: number | null;
  authorized_profile_ids?: number[];
}

export type NetcodeValidationStep = 'whatsapp' | 'nombre' | 'pin' | 'full' | 'cliente_acceso';

export interface NetflixProfileValidationResponse {
  status: 'success' | 'not_found' | 'error';
  step: NetcodeValidationStep;
  message: string;
  perfil?: {
    id?: number | null;
    nombre: string | null;
    pin: string | null;
    numero: string | null;
    vendedor: string | null;
    costo: string | number | null;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    vence: string | null;
    estado: string | null;
    ocupado: boolean;
    hoja_excel: string | null;
    fila_excel: number | null;
    cliente_acceso_usuario: string | null;
  };
  profiles?: Array<{
    nombre: string | null;
    pin: string | null;
    vence: string | null;
    estado: string | null;
    fila_excel: number | null;
  }>;
  cuenta?: {
    id?: number | null;
    email: string | null;
    password: string | null;
    producto: string | null;
    perfiles_total: number | null;
    perfiles_usados: number | null;
    activo: boolean;
    hoja_excel: string | null;
    fila_excel: number | null;
    cliente_acceso_usuario: string | null;
    bot_preferencia: 'principal' | 'personalizado';
    bot_hogar_url: string | null;
    bot_temporal_url: string | null;
    bot_acceso4_url: string | null;
    bot_acceso4_masked_url: string | null;
  };
}

@Injectable({ providedIn: 'root' })
export class NetcodeApi {
  private readonly http = inject(HttpClient);

  tutorials(): Observable<Record<string, Tutorial>> {
    return this.http
      .get<{ data: Record<string, Tutorial> }>('/api/v1/netcode/tutorials')
      .pipe(map((response) => response.data));
  }

  searchEmail(payload: {
    subject: NetcodeSearchSubject;
    email?: string;
    account_id?: number;
  }): Observable<NetcodeSearchResponse> {
    return this.http.post<NetcodeSearchResponse>('/api/v1/netcode/buscar-email', {
      email: payload.email,
      account_id: payload.account_id,
      subject: payload.subject,
    });
  }

  validateAccess(payload: {
    step: NetcodeValidationStep;
    numero?: string;
    nombre_perfil?: string;
    pin?: string;
    cliente_acceso_usuario?: string;
  }): Observable<NetflixProfileValidationResponse> {
    return this.http.post<NetflixProfileValidationResponse>('/api/v1/netcode/netflix-validar', payload);
  }
}

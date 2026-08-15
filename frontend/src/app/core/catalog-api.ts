import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { ApiCollection, PlataformaCatalogItem } from './api-types';

@Injectable({ providedIn: 'root' })
export class CatalogApi {
  private readonly http = inject(HttpClient);

  getPlataformas(): Observable<PlataformaCatalogItem[]> {
    return this.http
      .get<ApiCollection<PlataformaCatalogItem>>('/api/v1/plataformas')
      .pipe(map((response) => response.data));
  }
}

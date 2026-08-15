# Arquitectura

## Estado actual auditado

- Laravel 12.36.1, requerido por Composer como `^12.0`.
- PHP requerido: `^8.2`; entorno local detectado: PHP 8.3.27.
- Node local detectado: 24.13.0; npm 11.6.2.
- Backend actual: Laravel en `E:\mipaghe\backend`.
- Frontend Angular actual: `E:\mipaghe\frontend`.
- Public/document root: `E:\mipaghe\backend\public`.
- Frontend Laravel conservado: Blade + Livewire + Flux + Vite + Tailwind CSS.
- Autenticacion: Laravel Fortify con login, password reset, verificacion de email y 2FA.
- Admin actual: Blade autenticado para plataformas, tutoriales y rangos de importacion Excel.
- Procesos actuales: importacion Excel de Netflix Premium, procesamiento IMAP de correos y endpoints de validacion/busqueda.

## Funcionalidad existente que se conserva

- `/` y rutas `netcode/*` siguen sirviendo Blade.
- `/pago` y `/plataformas` siguen siendo vistas Blade.
- `/dashboard`, `settings/*` y `admin/*` siguen protegidos por autenticacion.
- Endpoints existentes: `/api/buscar-email`, `/api/netflix-validar`, `/ia-chat`.
- Comando existente: `emails:procesar-pedidos`.
- Comando existente: `excel:sync-netflix-premium`.

## Arquitectura objetivo progresiva

```text
Internet
  -> Nginx
      -> Laravel Blade actual
      -> Laravel API versionada futura
      -> Angular separado en `E:\mipaghe\frontend`
  -> MySQL interno
  -> Worker Laravel
  -> Redis futuro cuando el volumen lo justifique
```

Angular queda separado del backend Laravel. No debe volver a crearse una carpeta Angular dentro de `E:\mipaghe\backend`.

## Riesgos encontrados

- `.env` real existe localmente y contiene configuracion de produccion. No debe subirse a Git.
- El entorno local apunta a MySQL no disponible; por eso `migrate:status` y tests no funcionan localmente fuera de Docker.
- PHP local no tiene driver SQLite, lo que impide ejecutar tests con SQLite en memoria.
- Hay logs y sesiones dentro del arbol de trabajo. `.gitignore` fue endurecido para que no entren al repositorio.
- El endpoint de validacion Netflix devuelve credenciales de cuenta tras validar WhatsApp/perfil/PIN; se conserva por compatibilidad, pero debe auditarse antes de exponerlo a una SPA publica.
- Algunas migraciones usan SQL especifico de MySQL, por lo que MySQL debe ser la base principal de pruebas de integracion.

## Siguiente evolucion

1. Crear API `/api/v1/catalog` reutilizando `productos` y `plataformas`.
2. Agregar tablas compatibles: `pedidos`, `pedido_items`, `pagos`, `cupones`, `promociones`, `recommendation_rules`.
3. Continuar la migracion en `E:\mipaghe\frontend`.
4. Mantener `E:\mipaghe\backend` como backend/API Laravel.

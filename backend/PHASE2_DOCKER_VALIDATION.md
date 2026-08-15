# Fase 2 - Docker + validacion Laravel actual

Fecha: 2026-08-13

## Alcance

Esta fase mantiene el proyecto Laravel actual sin crear Angular y sin reemplazar Blade, Livewire, Fortify, rutas existentes, panel administrativo, importacion Excel, IMAP ni endpoints actuales.

Arquitectura validada:

```text
Nginx -> Laravel/PHP-FPM -> MySQL
                    -> Worker Laravel
```

## Archivos modificados

- `docker/backend/Dockerfile`
- `docker-compose.yml`
- `docker-compose.prod.yml`
- `database/migrations/2025_11_18_041820_create_emails_pedidos_table.php`
- `tests/Feature/Auth/RegistrationTest.php`
- `DOCKER.md`
- `DEPLOYMENT.md`
- `PHASE2_DOCKER_VALIDATION.md`

## Problemas encontrados

- El daemon de Docker Desktop no estaba activo al inicio de la validacion.
- El puerto `8080` ya estaba ocupado en la maquina local, por lo que la validacion HTTP se hizo en `8081`.
- El PHP local no tiene `sqlite` / `pdo_sqlite`; por eso la suite de tests no es reproducible correctamente fuera de Docker en esta maquina.
- `migrate:status` local intenta usar el MySQL configurado en `.env`, que no estaba disponible desde el host.
- El Dockerfile anterior instalaba Composer sin dependencias de desarrollo, lo que impedia correr tests dentro del contenedor de desarrollo.
- El primer build real fallo compilando `imap` porque faltaba `libc-client2007e-dev`.
- La imagen base `php:8.3-fpm-bookworm` ya incluye `pdo_sqlite` y `sqlite3`; intentar compilarlos de nuevo generaba conflicto.
- Composer fallo porque `phpoffice/phpspreadsheet` requiere `ext-gd`.
- Los tests con SQLite fallaban porque la migracion `emails_pedidos` usaba sintaxis especifica de MySQL: `asunto(191)`.
- Dos tests de registro asumian que Fortify tenia registro publico activo, pero la configuracion actual no habilita `Features::registration()`.

## Problemas solucionados

- El Dockerfile ahora instala las extensiones necesarias para Laravel actual, MySQL, IMAP, importacion Excel y tests:
  - `bcmath`
  - `gd`
  - `intl`
  - `imap`
  - `pdo_mysql`
  - `pdo_sqlite`
  - `sqlite3`
  - `zip`
- `docker-compose.yml` usa `INSTALL_DEV=true` para que el contenedor de desarrollo incluya dependencias de testing.
- `docker-compose.prod.yml` usa `INSTALL_DEV=false` para produccion.
- El Dockerfile instala `libc-client2007e-dev`, requerido por la extension PHP `imap`.
- El Dockerfile ya no recompila `pdo_sqlite` ni `sqlite3` porque vienen activos en la imagen base.
- El Dockerfile instala y habilita `gd`, requerido por `phpoffice/phpspreadsheet`.
- La migracion `emails_pedidos` conserva el indice optimizado para MySQL y usa un indice compatible para SQLite/testing.
- Los tests de registro ahora se saltan si las rutas de registro no estan habilitadas, respetando la configuracion actual de Fortify.

## Comandos ejecutados

Validacion de Docker Compose:

```bash
docker compose --env-file .env.example config --quiet
docker compose --env-file .env.example -f docker-compose.yml -f docker-compose.prod.yml config --quiet
```

Build y arranque:

```bash
docker compose --env-file .env.example build backend
docker compose --env-file .env.example up -d mysql backend nginx
docker compose --env-file .env.example up -d worker
docker compose --env-file .env.example ps
```

Nota: para esta validacion se uso un `APP_KEY` temporal generado en la shell y no persistido en archivos. Para uso diario debe existir un `.env` local real o una variable `APP_KEY` configurada.

Laravel dentro de Docker:

```bash
docker compose exec -T backend php artisan about
docker compose exec -T backend php artisan migrate --force
docker compose exec -T backend php artisan migrate:status
docker compose exec -T backend php artisan db:seed --force
docker compose exec -T backend php artisan route:list --except-vendor
docker compose exec -T backend php artisan test
```

Tests con entorno aislado:

```bash
docker compose exec -T \
  -e APP_ENV=testing \
  -e APP_DEBUG=true \
  -e CACHE_STORE=array \
  -e SESSION_DRIVER=array \
  -e QUEUE_CONNECTION=sync \
  -e MAIL_MAILER=array \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=':memory:' \
  backend php artisan test
```

Calidad y frontend existente:

```bash
vendor/bin/pint --test
npm run build
```

Verificacion HTTP:

```bash
curl.exe -I http://localhost:8081/
curl.exe -I http://localhost:8081/login
curl.exe -I http://localhost:8081/pago
curl.exe -I http://localhost:8081/plataformas
curl.exe -I http://localhost:8081/dashboard
curl.exe -I http://localhost:8081/admin/plataformas
curl.exe -I http://localhost:8081/up
```

## Resultado de Docker

Servicios levantados correctamente:

- `lg-mysql-1`: healthy
- `lg-backend-1`: healthy
- `lg-nginx-1`: activo en `http://localhost:8081`
- `lg-worker-1`: activo

MySQL no queda expuesto publicamente; solo esta disponible dentro de la red Docker.

## Resultado de migraciones

`php artisan migrate --force` ejecuto correctamente todas las migraciones existentes.

`php artisan migrate:status` muestra todas las migraciones como `Ran`, incluyendo:

- usuarios, cache y jobs base de Laravel
- plataformas
- tutoriales
- emails procesados
- tickets
- imports Excel
- emails de pedidos
- sesiones

## Resultado de seeders

`php artisan db:seed --force` se ejecuto correctamente en Docker.

Importante: el seeder actual crea un usuario de prueba (`test@example.com`). Es aceptable para desarrollo/staging, pero no debe ejecutarse automaticamente en produccion.

## Resultado de tests

Suite final dentro de Docker con entorno `testing` y SQLite en memoria:

```text
30 passed, 2 skipped, 66 assertions
```

Los dos tests saltados corresponden a registro publico, porque Fortify no tiene habilitadas las rutas de registro en la configuracion actual.

## Resultado del build

`npm run build` finalizo correctamente con Vite.

Observacion: Vite informa `Generated an empty chunk: "app"`. No rompe el build; indica que el entrypoint JS actual no genera contenido relevante. Se conserva porque no estamos reemplazando Blade ni el frontend existente en esta fase.

## Estado de rutas principales

Rutas publicas verificadas por HTTP:

- `/`: `200 OK`
- `/login`: `200 OK`
- `/pago`: `200 OK`
- `/plataformas`: `200 OK`
- `/up`: `200 OK`

Rutas protegidas verificadas sin sesion:

- `/dashboard`: `302 Found` hacia `/login`
- `/admin/plataformas`: `302 Found` hacia `/login`

Este comportamiento es correcto para autenticacion existente.

`php artisan route:list --except-vendor` se ejecuto correctamente y conserva las rutas actuales de Blade, Fortify, Livewire, admin, Excel, IMAP/API y endpoints existentes.

## Bloqueos o dependencias de la maquina

- El puerto `8080` esta ocupado en esta maquina; la validacion quedo en `http://localhost:8081`.
- Para reiniciar sin perder sesiones cifradas, debe usarse un `.env` local real con `APP_KEY` estable. En esta validacion no se guardo ninguna clave real en el repositorio.
- El PHP local del host no tiene SQLite; los tests deben ejecutarse dentro de Docker o se debe instalar/habilitar `pdo_sqlite` localmente.
- El directorio actual no es un repositorio Git inicializado, por lo que no se pudo validar estado de ramas `development/staging/main` ni preparar commits.
- Existe un `.env` real local con secretos. No se imprimio su contenido y no debe subirse a GitHub.

## Estado final

Fase 2 validada: el Laravel actual funciona en Docker con Nginx, PHP-FPM, MySQL y worker. Se ejecutaron migraciones, seeders de desarrollo, tests, build de frontend existente y verificaciones HTTP principales. No se creo Angular ni se reemplazo Blade.

# Docker

## Servicios

- `nginx`: entrada HTTP publica. En produccion compila Angular y sirve la SPA.
- `backend`: Laravel PHP-FPM.
- `worker`: `php artisan queue:work`.
- `mysql`: MySQL 8.4 interno, sin puerto publico.

## Desarrollo local

1. Copia `.env.example` a `.env` y genera `APP_KEY`.
2. Ajusta valores `MYSQL_*`, `APP_URL` y `CRON_TOKEN`.
3. Levanta servicios:

```bash
docker compose up -d --build
```

4. Ejecuta migraciones manualmente:

```bash
docker compose exec backend php artisan migrate
```

5. Ejecuta seeders solo en desarrollo o staging:

```bash
docker compose exec backend php artisan db:seed
```

6. Verifica el Laravel actual:

```bash
docker compose exec backend php artisan about
docker compose exec backend php artisan migrate:status
docker compose exec backend php artisan route:list
docker compose exec backend php artisan test
npm run build
```

7. Abre:

```text
http://localhost:8080
```

## Produccion

En la VM, usar:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

En produccion:

- Angular se compila dentro de `docker/nginx/Dockerfile`.
- El build de Angular usa `NODE_OPTIONS=--max-old-space-size=1536` y `NG_BUILD_MAX_WORKERS=1` para funcionar en VM pequenas.
- Nginx sirve `frontend/dist/frontend/browser` como frontend principal.
- Nginx envia `/api/*`, `/login`, `/logout`, `/livewire`, `/cron`, `/payment-media`, `/tutorial-media` y rutas backend necesarias a Laravel PHP-FPM.
- Las rutas Angular usan fallback a `index.html`.
- `backend` y `worker` reutilizan la misma imagen `lg-backend`; no se compilan dos veces en produccion.
- MySQL no publica puertos externos.
- `mysql_data` persiste MySQL.
- `app_storage` persiste `storage` para backend y worker.
- `APP_ENV=production` y `APP_DEBUG=false` se fuerzan desde `docker-compose.prod.yml`.

En VM pequenas, construir con un solo job de Compose evita presion de memoria:

```bash
COMPOSE_PARALLEL_LIMIT=1 docker compose -f docker-compose.yml -f docker-compose.prod.yml build
```

No ejecutes migraciones destructivas automaticamente. Primero backup, luego `php artisan migrate`, luego verificacion.

La imagen de desarrollo instala dependencias Composer de desarrollo para permitir tests. La combinacion con `docker-compose.prod.yml` usa `INSTALL_DEV=false`.

## Administrador inicial

Configura el primer administrador solo en el `.env` real de la VM:

```env
ADMIN_NAME="Administrador"
ADMIN_EMAIL=
ADMIN_PASSWORD=
```

Luego ejecuta:

```bash
docker compose exec backend php artisan db:seed --class=InitialAdminSeeder
```

Tambien puedes usar el comando equivalente:

```bash
docker compose exec backend php artisan admin:ensure-user
```

Ambos leen `ADMIN_EMAIL` y `ADMIN_PASSWORD`, crean el usuario si no existe, lo actualizan si ya existe, asignan rol `admin` y no imprimen la contrasena.

## Backup MySQL

El script base esta en `docker/mysql-backup.sh`. Debe ejecutarse desde un contenedor con `mysqldump` y escribir fuera de la VM o sincronizarse a almacenamiento externo.

Restauracion:

```bash
gunzip -c backup.sql.gz | docker compose exec -T mysql mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"
```

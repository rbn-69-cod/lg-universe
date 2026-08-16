# Docker

## Servicios

- `nginx`: entrada HTTP/HTTPS publica. En produccion compila Angular y sirve la SPA.
- `backend`: Laravel PHP-FPM.
- `worker`: `php artisan queue:work`.
- `mysql`: MySQL 8.4 interno, sin puerto publico.
- `certbot`: emision/renovacion de certificados Let's Encrypt mediante webroot.

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
- Los certificados Let's Encrypt persisten en el volumen `letsencrypt`.
- Los challenges ACME persisten en `certbot_challenges` y Nginx los sirve desde `/.well-known/acme-challenge/`.
- Nginx publica `80:80` para redireccion/renovacion ACME y `443:443` para HTTPS.

En VM pequenas, construir con un solo job de Compose evita presion de memoria:

```bash
COMPOSE_PARALLEL_LIMIT=1 docker compose -f docker-compose.yml -f docker-compose.prod.yml build
```

No ejecutes migraciones destructivas automaticamente. Primero backup, luego `php artisan migrate`, luego verificacion.

La imagen de desarrollo instala dependencias Composer de desarrollo para permitir tests. La combinacion con `docker-compose.prod.yml` usa `INSTALL_DEV=false`.

## HTTPS con Let's Encrypt

Nginx corre dentro de Docker. No uses `certbot --nginx` en el host.

El contenedor Nginx genera un certificado temporal de 1 dia solo si todavia no existe `/etc/letsencrypt/live/igruben.lat/fullchain.pem`. Esto permite que Nginx arranque antes de emitir el certificado real. Ese certificado temporal vive en el volumen Docker, no en Git.

Nginx monta `certbot_challenges` como solo lectura. El directorio `/.well-known/acme-challenge/` debe ser creado por el servicio `certbot`, que monta el mismo volumen con escritura.

Primer certificado para `igruben.lat`:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm certbot certonly \
  --webroot \
  -w /var/www/certbot \
  -d igruben.lat \
  --email TU_CORREO_REAL \
  --agree-tos \
  --no-eff-email \
  --force-renewal
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec nginx nginx -s reload
```

No agregues `www.igruben.lat` al certificado hasta verificar que su DNS exista y apunte a la VM.

Verificacion:

```bash
curl -I http://igruben.lat
curl -I https://igruben.lat
curl -I https://igruben.lat/api/v1/payment-settings
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm certbot certificates
```

Renovacion manual:

```bash
sh scripts/renew-certificates.sh
```

Renovacion automatica recomendada en la VM con cron del host:

```cron
17 3 * * * cd /var/www/lg-universe/backend && sh scripts/renew-certificates.sh >> /var/log/lg-universe-certbot.log 2>&1
```

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
Docker pasa estas variables al contenedor mediante `env_file: .env`; no deben declararse con correos o passwords por defecto en `docker-compose.yml`.

## Backup MySQL

El script base esta en `docker/mysql-backup.sh`. Debe ejecutarse desde un contenedor con `mysqldump` y escribir fuera de la VM o sincronizarse a almacenamiento externo.

Restauracion:

```bash
gunzip -c backup.sql.gz | docker compose exec -T mysql mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"
```

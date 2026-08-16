# Deployment

## Flujo objetivo

```text
development -> staging -> main -> VM Starlight -> Docker -> Produccion
```

## Primer despliegue en VM

1. Instalar Docker y Docker Compose.
2. Clonar el repositorio desde GitHub.
3. Crear `.env` real en la VM desde `.env.example`.
   Configurar tambien el primer administrador sin guardar credenciales en Git:

```env
ADMIN_NAME="Administrador"
ADMIN_EMAIL=
ADMIN_PASSWORD=
```

4. Ejecutar build:

```bash
COMPOSE_PARALLEL_LIMIT=1 docker compose -f docker-compose.yml -f docker-compose.prod.yml build
```

Este build genera:

- imagen `lg-nginx` con Angular compilado y configuracion Nginx de produccion;
- imagen `lg-backend` para Laravel PHP-FPM;
- worker reutilizando `lg-backend` para colas, IMAP y tareas asincronas.

5. Levantar servicios:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

La entrada publica es Nginx en puertos `80` y `443`. Angular es el frontend principal y Laravel queda detras de Nginx para API, autenticacion, media privada, cron y endpoints backend.

Antes del certificado real, Nginx puede arrancar con un certificado temporal generado dentro del volumen Docker. Luego se emite Let's Encrypt por webroot:

```bash
CERTBOT_EMAIL=TU_CORREO_REAL sh scripts/issue-certificate.sh
```

No usar `certbot --nginx` en el host porque Nginx esta dentro del contenedor `lg-nginx`. El volumen de challenges ACME lo escribe el servicio `certbot`; Nginx lo monta como solo lectura.

6. Ejecutar migraciones solo tras backup:

```bash
docker compose exec backend php artisan migrate
```

7. Crear o actualizar el administrador inicial desde variables de entorno:

```bash
docker compose exec backend php artisan db:seed --class=InitialAdminSeeder
```

El seeder es idempotente: si el correo ya existe, actualiza nombre, rol `admin`, verificacion y password usando `ADMIN_PASSWORD`. No imprime la contrasena.

8. Verificar:

```bash
docker compose exec backend php artisan route:list
docker compose exec backend php artisan queue:work --once
curl -I https://igruben.lat
curl -I https://igruben.lat/api/v1/payment-settings
echo | openssl s_client -connect igruben.lat:443 -servername igruben.lat 2>/dev/null | openssl x509 -noout -issuer -subject -dates
```

## Renovacion SSL

Certbot usa los volumenes persistentes `letsencrypt` y `certbot_challenges`.

Renovacion manual:

```bash
sh scripts/renew-certificates.sh
```

Renovacion automatica recomendada en la VM:

```cron
17 3 * * * cd /var/www/lg-universe/backend && sh scripts/renew-certificates.sh >> /var/log/lg-universe-certbot.log 2>&1
```

## Rollback

1. Detener servicios.
2. Volver al commit anterior.
3. Reconstruir imagen.
4. Restaurar backup de base de datos solo si una migracion cambio datos de forma incompatible.

## Migraciones

No automatizar migraciones destructivas en produccion. El procedimiento correcto es:

```text
Backup -> migrate -> verificar rutas/auth/datos -> monitorear logs
```

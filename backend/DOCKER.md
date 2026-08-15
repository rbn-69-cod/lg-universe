# Docker

## Servicios

- `nginx`: entrada HTTP publica.
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

No ejecutes migraciones destructivas automaticamente. Primero backup, luego `php artisan migrate`, luego verificacion.

La imagen de desarrollo instala dependencias Composer de desarrollo para permitir tests. La combinacion con `docker-compose.prod.yml` usa `INSTALL_DEV=false`.

## Backup MySQL

El script base esta en `docker/mysql-backup.sh`. Debe ejecutarse desde un contenedor con `mysqldump` y escribir fuera de la VM o sincronizarse a almacenamiento externo.

Restauracion:

```bash
gunzip -c backup.sql.gz | docker compose exec -T mysql mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"
```

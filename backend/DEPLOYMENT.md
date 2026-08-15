# Deployment

## Flujo objetivo

```text
development -> staging -> main -> VM Starlight -> Docker -> Produccion
```

## Primer despliegue en VM

1. Instalar Docker y Docker Compose.
2. Clonar el repositorio desde GitHub.
3. Crear `.env` real en la VM desde `.env.example`.
4. Ejecutar build:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml build
```

5. Levantar servicios:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

6. Ejecutar migraciones solo tras backup:

```bash
docker compose exec backend php artisan migrate
```

7. No ejecutar `db:seed` en produccion salvo que exista un seeder especifico e idempotente para datos base. El seeder actual crea un usuario de prueba.

8. Verificar:

```bash
docker compose exec backend php artisan route:list
docker compose exec backend php artisan queue:work --once
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

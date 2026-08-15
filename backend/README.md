# LG Universe Laravel

Proyecto Laravel existente modernizado de forma progresiva. La base actual usa Laravel 12, Blade, Livewire, Fortify, Vite y Tailwind.

## Estado actual

- Laravel 12.36.1.
- PHP `^8.2`.
- Frontend actual en Blade/Livewire.
- Admin actual para plataformas, tutoriales y rangos Excel.
- Procesamiento Excel e IMAP existente.
- Docker base agregado para Nginx, backend, MySQL y worker.

## Desarrollo con Docker

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec backend php artisan key:generate
docker compose exec backend php artisan migrate
```

Aplicacion:

```text
http://localhost:8080
```

## Documentacion

- `ARCHITECTURE.md`
- `DOCKER.md`
- `ENVIRONMENT.md`
- `DEPLOYMENT.md`

## Importante

No subir `.env`, logs, sesiones, cache, `vendor`, `node_modules` ni builds generados. La migracion a Angular debe hacerse en una fase posterior, manteniendo Blade activo hasta que las APIs esten listas.

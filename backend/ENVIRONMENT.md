# Variables de Entorno

El archivo real `.env` no debe subirse a GitHub. La plantilla mantenida es `.env.example`.

## Grupos principales

- APP: `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`.
- DATABASE: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- DOCKER MYSQL: `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`.
- MAIL: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`.
- IMAP: `IMAP_MAILBOX`, `IMAP_USERNAME`, `IMAP_PASSWORD`, `EMAILS_PEDIDOS_TABLE`, `EMAILS_MAX_MINUTES`.
- CACHE/QUEUE: `CACHE_STORE`, `QUEUE_CONNECTION`, `QUEUE_WORKER_TRIES`, `QUEUE_WORKER_TIMEOUT`.
- SERVICES: `CRON_TOKEN`, `OPENROUTER_API_KEY`, `OPENROUTER_MODEL`.

## Reglas

- Produccion debe usar `APP_ENV=production`, `APP_DEBUG=false` y `APP_KEY` generado en la VM.
- MySQL no debe exponerse publicamente.
- IMAP debe usarse solo con buzones autorizados.
- No registrar tokens, passwords, `APP_KEY`, credenciales IMAP ni respuestas externas completas.

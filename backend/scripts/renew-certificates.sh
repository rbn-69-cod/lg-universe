#!/bin/sh
set -eu

cd "$(dirname "$0")/.."

docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm certbot renew --webroot -w /var/www/certbot --quiet
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec nginx nginx -s reload

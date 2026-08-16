#!/bin/sh
set -eu

cd "$(dirname "$0")/.."

domain="${SSL_DOMAIN:-igruben.lat}"
email="${CERTBOT_EMAIL:-${1:-}}"

if [ -z "${email}" ]; then
    echo "Missing certificate email. Use CERTBOT_EMAIL=correo@dominio.com sh scripts/issue-certificate.sh" >&2
    exit 1
fi

docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d nginx

docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm --entrypoint sh certbot -c "
set -eu
domain='${domain}'
stamp=\"\$(date +%Y%m%d%H%M%S)\"
mkdir -p /var/www/certbot/.well-known/acme-challenge /etc/letsencrypt/live /etc/letsencrypt/archive /etc/letsencrypt/renewal

if [ -d \"/etc/letsencrypt/live/\${domain}\" ] && [ ! -f \"/etc/letsencrypt/renewal/\${domain}.conf\" ]; then
    mv \"/etc/letsencrypt/live/\${domain}\" \"/etc/letsencrypt/live/\${domain}.bootstrap-\${stamp}\"
fi

if [ -d \"/etc/letsencrypt/archive/\${domain}\" ] && [ ! -f \"/etc/letsencrypt/renewal/\${domain}.conf\" ]; then
    mv \"/etc/letsencrypt/archive/\${domain}\" \"/etc/letsencrypt/archive/\${domain}.bootstrap-\${stamp}\"
fi
"

docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm certbot certonly \
    --webroot \
    -w /var/www/certbot \
    -d "${domain}" \
    --email "${email}" \
    --agree-tos \
    --no-eff-email \
    --non-interactive \
    --keep-until-expiring

docker compose -f docker-compose.yml -f docker-compose.prod.yml exec nginx nginx -s reload

#!/bin/sh
set -eu

cd "$(dirname "$0")/.."

domain="${SSL_DOMAIN:-igruben.lat}"
email="${CERTBOT_EMAIL:-${1:-}}"
compose_files="-f docker-compose.yml -f docker-compose.prod.yml"

if [ -z "${email}" ]; then
    echo "Missing certificate email. Use CERTBOT_EMAIL=correo@dominio.com sh scripts/issue-certificate.sh" >&2
    exit 1
fi

docker compose ${compose_files} up -d nginx

issuer="$(docker compose ${compose_files} exec -T nginx sh -c "if [ -s /etc/letsencrypt/live/${domain}/fullchain.pem ]; then openssl x509 -in /etc/letsencrypt/live/${domain}/fullchain.pem -noout -issuer 2>/dev/null || true; fi")"
is_lets_encrypt=0

if printf '%s' "${issuer}" | grep -qi "Let's Encrypt"; then
    is_lets_encrypt=1
    echo "Nginx is already serving a Let's Encrypt certificate for ${domain}."
else
    echo "Current certificate is not Let's Encrypt. Preparing real ACME issuance for ${domain}."
fi

docker compose ${compose_files} run --rm --entrypoint sh certbot -c "
set -eu
domain='${domain}'
is_lets_encrypt='${is_lets_encrypt}'
stamp=\"\$(date +%Y%m%d%H%M%S)\"
mkdir -p /var/www/certbot/.well-known/acme-challenge /etc/letsencrypt/live /etc/letsencrypt/archive /etc/letsencrypt/renewal

if [ \"\${is_lets_encrypt}\" != \"1\" ]; then
    if [ -d \"/etc/letsencrypt/live/\${domain}\" ]; then
        mv \"/etc/letsencrypt/live/\${domain}\" \"/etc/letsencrypt/live/\${domain}.bootstrap-\${stamp}\"
    fi

    if [ -d \"/etc/letsencrypt/archive/\${domain}\" ]; then
        mv \"/etc/letsencrypt/archive/\${domain}\" \"/etc/letsencrypt/archive/\${domain}.bootstrap-\${stamp}\"
    fi

    if [ -f \"/etc/letsencrypt/renewal/\${domain}.conf\" ]; then
        mv \"/etc/letsencrypt/renewal/\${domain}.conf\" \"/etc/letsencrypt/renewal/\${domain}.bootstrap-\${stamp}.conf\"
    fi
fi
"

probe="lg-universe-acme-$(date +%s)"

docker compose ${compose_files} run --rm --entrypoint sh certbot -c "
set -eu
mkdir -p /var/www/certbot/.well-known/acme-challenge
printf '%s' '${probe}' > '/var/www/certbot/.well-known/acme-challenge/${probe}'
"

body="$(curl -fsS --max-time 20 "http://${domain}/.well-known/acme-challenge/${probe}" || true)"

docker compose ${compose_files} run --rm --entrypoint sh certbot -c "rm -f '/var/www/certbot/.well-known/acme-challenge/${probe}'"

if [ "${body}" != "${probe}" ]; then
    echo "ACME HTTP challenge is not publicly reachable." >&2
    echo "Expected: http://${domain}/.well-known/acme-challenge/${probe}" >&2
    echo "Check DNS, port 80, firewall and nginx logs before retrying." >&2
    exit 1
fi

docker compose ${compose_files} run --rm certbot certonly \
    --webroot \
    -w /var/www/certbot \
    -d "${domain}" \
    --cert-name "${domain}" \
    --email "${email}" \
    --agree-tos \
    --no-eff-email \
    --non-interactive \
    --force-renewal

docker compose ${compose_files} exec nginx nginx -s reload
docker compose ${compose_files} exec -T nginx sh -c "openssl x509 -in /etc/letsencrypt/live/${domain}/fullchain.pem -noout -issuer -subject -dates"

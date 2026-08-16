#!/bin/sh
set -eu

domain="${SSL_DOMAIN:-igruben.lat}"
live_dir="/etc/letsencrypt/live/${domain}"
cert_path="${live_dir}/fullchain.pem"
key_path="${live_dir}/privkey.pem"

if [ ! -s "${cert_path}" ] || [ ! -s "${key_path}" ]; then
    mkdir -p "${live_dir}"
    openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
        -keyout "${key_path}" \
        -out "${cert_path}" \
        -subj "/CN=${domain}" >/dev/null 2>&1
    echo "Created temporary self-signed certificate for ${domain}. Replace it with Let's Encrypt using certbot webroot."
fi

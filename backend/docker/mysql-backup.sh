#!/usr/bin/env sh
set -e

BACKUP_DIR="${BACKUP_DIR:-/backups}"
STAMP="$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

mysqldump \
  -h "${DB_HOST:-mysql}" \
  -u "${DB_USERNAME:-laravel}" \
  -p"${DB_PASSWORD}" \
  "${DB_DATABASE:-laravel}" \
  | gzip > "$BACKUP_DIR/mysql_${DB_DATABASE:-laravel}_${STAMP}.sql.gz"

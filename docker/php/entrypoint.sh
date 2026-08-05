#!/bin/bash
#
# @author Bodo Desderio <rooiboktechltd@gmail.com>
# @copyright 2026 Rooibok Technologies. All rights reserved.
#
# Container entrypoint — makes `docker compose up -d --build` a single, complete
# boot: fix writable/upload ownership, wait for Postgres, apply schema migrations
# and seed first-boot assets (both idempotent), then hand off to the CMD.
#
# Runs as root so it can chown the (root-created) named volumes; the actual PHP
# work and the long-running process drop to the app uid (1000) via gosu. php-fpm
# is the exception — its master must stay root to spawn workers as the pool user.
set -e

APP_UID="${APP_UID:-1000}"
APP_GID="${APP_GID:-1000}"

# 0. Fresh-checkout bootstrap: generate a CI4 .env if one doesn't exist, so a bare
#    `docker compose up -d --build` works locally with zero manual config. Only
#    when MISSING — never overwrite an existing .env (that would rotate
#    ENCRYPTION_KEY and orphan every encrypted value already in the database).
ENV_FILE=/var/www/html/.env
if [ ! -f "$ENV_FILE" ]; then
  echo "[entrypoint] no .env — generating one for local boot"
  if [ -f /var/www/html/.env.example ]; then cp /var/www/html/.env.example "$ENV_FILE"; else : > "$ENV_FILE"; fi
  set_kv() { # key value
    if grep -q "^$1=" "$ENV_FILE"; then sed -i "s|^$1=.*|$1=$2|" "$ENV_FILE"; else echo "$1=$2" >> "$ENV_FILE"; fi
  }
  set_kv DB_HOST     "${DB_HOST:-postgres}"
  set_kv DB_NAME     "${DB_NAME:-rooibok_hr}"
  set_kv DB_USER     "${DB_USER:-rooibok_user}"
  set_kv DB_PASS     "${DB_PASS:-rooibok_local_pw}"
  set_kv APP_BASEURL "http://localhost:12000"
  set_kv ENCRYPTION_KEY "hex2bin:$(php -r 'echo bin2hex(random_bytes(32));')"
  chown "${APP_UID}:${APP_GID}" "$ENV_FILE" 2>/dev/null || true
  echo "[entrypoint] .env created (DB creds + fresh ENCRYPTION_KEY)."
fi

# 1. Ownership: fpm workers + CLI run as uid 1000 (the host bind-mount owner), so
#    the writable tree and the uploads volume must be writable by 1000.
mkdir -p /var/www/html/writable /var/www/html/public/uploads
chown -R "${APP_UID}:${APP_GID}" /var/www/html/writable /var/www/html/public/uploads 2>/dev/null || true

# 2. Wait for the database to accept connections.
DB_HOST="${DB_HOST:-postgres}"
DB_PORT="${DB_PORT:-5432}"
echo "[entrypoint] waiting for database ${DB_HOST}:${DB_PORT} ..."
until php -r '$c=@fsockopen(getenv("DB_HOST")?:"postgres",(int)(getenv("DB_PORT")?:5432),$e,$s,2); exit($c?0:1);'; do
  sleep 2
done
echo "[entrypoint] database is up."

# 3. Schema + first-boot seed — only for the primary app role (RUN_INIT=1), so the
#    queue worker and other replicas don't race on migrations.
if [ "${RUN_INIT:-0}" = "1" ]; then
  echo "[entrypoint] applying migrations ..."
  gosu "${APP_UID}:${APP_GID}" php /var/www/html/spark migrate --all || echo "[entrypoint] WARN: migrate returned non-zero"
  echo "[entrypoint] seeding default assets + archive schema ..."
  gosu "${APP_UID}:${APP_GID}" php /var/www/html/spark app:init || echo "[entrypoint] WARN: app:init returned non-zero"
  echo "[entrypoint] init complete."
fi

# 4. Hand off. php-fpm keeps a root master (spawns workers as the remapped
#    www-data = uid 1000); everything else drops straight to the app uid.
if [ "$1" = "php-fpm" ]; then
  echo "[entrypoint] starting php-fpm"
  exec "$@"
else
  echo "[entrypoint] starting: $*"
  exec gosu "${APP_UID}:${APP_GID}" "$@"
fi

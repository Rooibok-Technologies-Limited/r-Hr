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

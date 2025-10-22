#!/usr/bin/env bash
set -e

cd /var/www/html

# Forza PHP-FPM a portare gli errori su stderr (visibili su Koyeb)
sed -i 's|^;*catch_workers_output =.*|catch_workers_output = yes|' /etc/php83/php-fpm.d/www.conf
grep -q 'php_admin_value\[error_log\]' /etc/php83/php-fpm.d/www.conf || echo 'php_admin_value[error_log] = /proc/self/fd/2' >> /etc/php83/php-fpm.d/www.conf
grep -q 'php_admin_flag\[log_errors\]' /etc/php83/php-fpm.d/www.conf || echo 'php_admin_flag[log_errors] = on' >> /etc/php83/php-fpm.d/www.conf


# 1) Materializza la config nginx con $PORT
export PORT="${PORT:-8080}"
envsubst '$PORT' </etc/nginx/http.d/default.conf.template >/etc/nginx/http.d/default.conf
nginx -t || (cat /etc/nginx/http.d/default.conf && exit 1)

# 2) Assicura un .env (minimo) se manca. Le ENV di Koyeb sovrascrivono comunque.
if [ ! -f ".env" ]; then
  echo "APP_ENV=production" > .env
  echo "APP_DEBUG=false" >> .env
  echo "APP_URL=${APP_URL:-https://example.com}" >> .env
fi

# 3) Chiave app
if [ -z "${APP_KEY}" ]; then
  echo "APP_KEY mancante: genero in .env..."
  php83 artisan key:generate --force --no-interaction || true
else
  echo "APP_KEY presente nelle ENV."
fi

# 4) Cache & link
php83 artisan config:clear || true
php83 artisan storage:link || true

# 5) Migrazioni se DB configurato
if [ -n "${DATABASE_URL}${DB_CONNECTION}${DB_HOST}${DB_DATABASE}" ]; then
  php83 artisan migrate --force || true
fi

# 6) Ottimizzazioni (ora che .env è pronto)
php83 artisan route:cache || true
php83 artisan view:cache || true
php83 artisan config:cache || true

# 7) Avvia supervisor (php-fpm + nginx)
exec /usr/bin/supervisord -c /etc/supervisord.conf
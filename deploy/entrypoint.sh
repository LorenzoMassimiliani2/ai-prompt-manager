#!/usr/bin/env bash
set -e

cd /var/www/html

# ───────────────────── DEBUG ─────────────────────
echo "--- DEBUG: Contenuto di /var/www/html ---"
ls -la || true
echo "-----------------------------------------"
echo "--- DEBUG: Contenuto di /var/www/html/resources ---"
ls -la resources || echo "La cartella 'resources' non esiste."
echo "---------------------------------------------------"

# ───────────── PHP-FPM: invia log su stderr ─────────
# (Il pacchetto è php83 su Alpine; il binario è 'php', ma la conf è in /etc/php83)
sed -i 's|^;*catch_workers_output =.*|catch_workers_output = yes|' /etc/php83/php-fpm.d/www.conf
grep -q 'php_admin_value\[error_log\]' /etc/php83/php-fpm.d/www.conf || echo 'php_admin_value[error_log] = /proc/self/fd/2' >> /etc/php83/php-fpm.d/www.conf
grep -q 'php_admin_flag\[log_errors\]' /etc/php83/php-fpm.d/www.conf || echo 'php_admin_flag[log_errors] = on' >> /etc/php83/php-fpm.d/www.conf

# ───────────── Nginx: materializza $PORT ────────────
export PORT="${PORT:-8080}"
if [ -f /etc/nginx/http.d/default.conf.template ]; then
  envsubst '$PORT' </etc/nginx/http.d/default.conf.template >/etc/nginx/http.d/default.conf
fi
nginx -t || (echo "[NGINX] Config non valida:" && cat /etc/nginx/http.d/default.conf && exit 1)

# ───────────── .env minimo + APP_KEY placeholder ────
if [ ! -f ".env" ]; then
  echo "APP_ENV=production" > .env
  echo "APP_DEBUG=false" >> .env
  echo "APP_URL=${APP_URL:-https://example.com}" >> .env
fi
# Aggiungi la riga APP_KEY se assente
if ! grep -q '^APP_KEY=' .env ; then
  echo "APP_KEY=" >> .env
fi

# Genera chiave se non presente in ENV
if [ -z "${APP_KEY:-}" ]; then
  echo "APP_KEY mancante: genero in .env..."
  php artisan key:generate --force --no-interaction || true
else
  echo "APP_KEY presente nelle ENV."
fi

# ───────────── Cartelle necessarie Laravel ──────────
mkdir -p storage/framework/{cache,sessions,views} \
         bootstrap/cache \
         resources/views

# Link storage (idempotente) e pulizia cache
php artisan config:clear || true
php artisan storage:link || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

# ───────────── Migrazioni se DB configurato ─────────
# Consideriamo configurato se esiste almeno una di queste variabili:
if [ -n "${DATABASE_URL:-}${DB_CONNECTION:-}${DB_HOST:-}${DB_DATABASE:-}" ]; then
  php artisan migrate --force || true
fi

# ───────────── Cache/ottimizzazioni sicure ──────────
php artisan route:cache || true
# Fai view:cache solo se esiste almeno un file blade (evita "View path not found")
if find resources/views -type f -name '*.blade.php' -print -quit | grep -q .; then
  php artisan view:cache || true
else
  echo "[VIEW] Nessun blade trovato, salto view:cache"
fi
php artisan config:cache || true

# ───────────── Avvio processi ───────────────────────
exec /usr/bin/supervisord -c /etc/supervisord.conf

# ── 1) Composer: installa le dipendenze PHP senza dev ───────────────────────────
FROM composer:2 AS vendor
WORKDIR /app

# solo i file composer per sfruttare cache
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress --no-scripts --optimize-autoloader

# ora copia tutto il progetto e sistema autoload
COPY . .
# --- DEBUG 1: Controlla se le viste sono presenti nella prima fase ---
RUN echo "--- DEBUG (VENDOR STAGE): Contenuto di /app/resources/views ---" && ls -laR /app/resources/views || echo "--- DEBUG (VENDOR STAGE): /app/resources/views NON TROVATA O VUOTA ---"
RUN composer dump-autoload --optimize && php -v && composer -V

# ── 2) Node: build degli asset (Vite) ───────────────────────────────────────────
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* pnpm-lock.yaml* yarn.lock* ./
RUN if [ -f package-lock.json ]; then npm ci; \
    elif [ -f yarn.lock ]; then yarn install --frozen-lockfile; \
    elif [ -f pnpm-lock.yaml ]; then npm i -g pnpm && pnpm i --frozen-lockfile; \
    else npm i; fi

# COPIA vendor/tightenco/ziggy prima del build
COPY --from=vendor /app/vendor/tightenco/ziggy /app/vendor/tightenco/ziggy

# copia codice sorgente
COPY . .
# --- DEBUG 2: Controlla se le viste sono presenti anche nella fase frontend ---
RUN echo "--- DEBUG (FRONTEND STAGE): Contenuto di /app/resources/views ---" && ls -laR /app/resources/views || echo "--- DEBUG (FRONTEND STAGE): /app/resources/views NON TROVATA O VUOTA ---"
RUN npm run build

# ── 3) Runtime: nginx + php-fpm su Alpine ──────────────────────────────────────
FROM alpine:3.20

# Pacchetti base + PHP 8.3 con estensioni comuni + pgsql & mysql + envsubst
RUN apk add --no-cache \
    nginx supervisor curl bash gettext \
    php83 php83-fpm php83-opcache \
    php83-pdo php83-pdo_pgsql php83-pgsql php83-pdo_mysql \
    php83-mbstring php83-xml php83-ctype php83-json php83-tokenizer php83-fileinfo \
    php83-session php83-curl php83-bcmath php83-intl php83-zip php83-dom php83-gd

ENV APP_DIR=/var/www/html
WORKDIR $APP_DIR

# Copia app, vendor e assets in modo pulito
# 1. Copia il codice sorgente dalla cartella locale
COPY . $APP_DIR

# 2. Copia le dipendenze installate dalla fase 'vendor'
COPY --from=vendor /app/vendor $APP_DIR/vendor

# 3. Copia gli asset compilati dalla fase 'frontend'
COPY --from=frontend /app/public/build $APP_DIR/public/build

# --- DEBUG 3: Controlla se le viste sono presenti nell'immagine finale ---
RUN echo "--- DEBUG (RUNTIME STAGE): Contenuto di $APP_DIR/resources/views ---" && ls -laR $APP_DIR/resources/views || echo "--- DEBUG (RUNTIME STAGE): $APP_DIR/resources/views NON TROVATA O VUOTA ---"

# Configurazioni
COPY deploy/nginx.conf.template /etc/nginx/http.d/default.conf.template
COPY deploy/supervisord.conf /etc/supervisord.conf
COPY deploy/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Permessi storage/bootstrap
RUN mkdir -p storage/framework/{cache,sessions,views} \
 && chown -R nobody:nogroup storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Koyeb fornisce $PORT (esponi per completezza)
ENV PORT=8080
EXPOSE 8080

CMD ["/entrypoint.sh"]

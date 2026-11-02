FROM dunglas/frankenphp:latest-php8.3 AS base

WORKDIR /app

RUN install-php-extensions \
    pdo \
    redis \
    curl \
    mbstring \
    intl \
    opcache \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY . .

RUN composer run-script post-install-cmd --no-interaction || true

RUN mkdir -p log temp && \
    chown -R www-data:www-data log temp

ENV APP_ENV=production

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/_ok || exit 1

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

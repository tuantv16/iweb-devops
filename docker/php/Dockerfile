# syntax=docker/dockerfile:1

# Base: nginx + php-fpm trong 1 container (serversideup/php), chạy non-root, lắng nghe cổng 8080,
# document root mặc định là /var/www/html/public.
FROM serversideup/php:8.2-fpm-nginx AS base

# Tắt HTTPS trong container (TLS sẽ do load balancer / môi trường ngoài xử lý).
ENV SSL_MODE=off

WORKDIR /var/www/html


# ---------------------------------------------------------------------------
# dev: cài cả package dev (phpunit, pint...). Source được bind-mount khi chạy local
# nên image này chủ yếu cung cấp PHP + vendor/.
# ---------------------------------------------------------------------------
FROM base AS dev

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY --chown=www-data:www-data . .
RUN composer dump-autoload --optimize


# ---------------------------------------------------------------------------
# prod: dùng cho Staging/Production. Không có package dev, source nằm trong image.
# Cache config/route để runtime (khi đã có biến môi trường) - không cache lúc build.
# ---------------------------------------------------------------------------
FROM base AS prod

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist --no-dev

COPY --chown=www-data:www-data . .
RUN composer dump-autoload --optimize --no-dev

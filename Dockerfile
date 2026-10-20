# Imagen de la tienda y el panel (Laravel) para Railway u otro servicio de contenedores.
# Un solo contenedor corre: nginx + php-fpm + el planificador (schedule:work), para pagar un solo servicio.
# El microservicio de analítica es otro servicio: ml-service/Dockerfile.
ARG PHP_IMAGE=php:8.3-fpm-bookworm
ARG COMPOSER_IMAGE=composer:2

FROM ${COMPOSER_IMAGE} AS composer

FROM ${PHP_IMAGE}

# nginx + supervisor: web y planificador en el mismo contenedor
# tesseract-ocr-spa: OCR de comprobantes de pago
# default-mysql-client: mysqldump para los respaldos
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx supervisor gettext-base \
        tesseract-ocr tesseract-ocr-spa \
        default-mysql-client \
        unzip curl \
        libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql zip gd bcmath intl exif opcache \
    && apt-get purge -y --auto-remove libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev \
    && apt-get install -y --no-install-recommends libzip4 libpng16-16 libjpeg62-turbo libfreetype6 libicu72 \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Dependencias primero: se reutiliza la capa mientras no cambien composer.json/lock
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --no-autoloader

COPY . .
# dump-autoload ya ejecuta package:discover (post-autoload-dump); debe arrancar sin .env ni variables
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/private storage/app/public bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY deploy/php.ini /usr/local/etc/php/conf.d/zz-tienda.ini
COPY deploy/php-fpm.conf /usr/local/etc/php-fpm.d/zz-tienda.conf
COPY deploy/nginx.conf.template /etc/nginx/nginx.conf.template
COPY deploy/supervisord.conf /etc/supervisor/tienda.conf
COPY deploy/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV PORT=8080
EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

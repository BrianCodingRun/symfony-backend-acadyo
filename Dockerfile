FROM php:8.4-apache

ARG APP_ENV=test
ENV APP_ENV=${APP_ENV}

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev default-mysql-client netcat-openbsd wget \
    && docker-php-ext-install intl pdo pdo_mysql zip \
    && a2enmod rewrite

RUN apt-get update && apt-get install -y \
    libssl-dev \
    pkg-config \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

RUN wget https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh \
    -O /usr/local/bin/wait-for-it.sh \
    && chmod +x /usr/local/bin/wait-for-it.sh

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . .

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

# Installer Composer en build (prod only)
RUN if [ "$APP_ENV" = "prod" ]; then \
    composer install --no-dev --optimize-autoloader && composer run-script auto-scripts; \
    fi

EXPOSE 80
ENTRYPOINT ["docker/entrypoint.sh"]

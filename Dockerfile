# Image de base avec PHP 8.4 et Apache
FROM php:8.4-apache

ARG APP_ENV=test

# Installation des extensions PHP nécessaires pour Symfony
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    default-mysql-client \
    netcat-openbsd \
    wget \
    && docker-php-ext-install \
    intl \
    pdo \
    pdo_mysql \
    zip \
    && a2enmod rewrite

# Installation des extensions mongodb pour php
RUN apt-get update && apt-get install -y \
    libssl-dev \
    pkg-config \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Installer wait-for-it
RUN wget https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh \
    -O /usr/local/bin/wait-for-it.sh \
    && chmod +x /usr/local/bin/wait-for-it.sh

# Installation de Composer (gestionnaire de dépendances PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration Apache pour Symfony
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# Copie du code source dans le container
WORKDIR /var/www/html

# Copier le fichier .env AVANT l'installation des dépendances
COPY .env* ./

# Set environment
ENV APP_ENV=${APP_ENV}

# Installation des dépendances SANS auto-scripts pour éviter l'erreur
RUN if [ "$APP_ENV" = "prod" ]; then \
    composer install --no-dev --optimize-autoloader --no-scripts; \
    else \
    composer install --optimize-autoloader --no-scripts; \
    fi

# Copier tout le reste du code source
COPY . .

# Rendre entrypoint.sh exécutable
RUN chmod +x docker/entrypoint.sh

# Créer le dossier var avec les bonnes permissions
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/var

# Générer l'autoload optimisé
RUN composer dump-autoload --optimize

# Changer vers l'utilisateur www-data pour les opérations Symfony
USER www-data

# Revenir en root pour le démarrage d'Apache
USER root

# Port exposé
EXPOSE 80

ENTRYPOINT ["docker/entrypoint.sh"]
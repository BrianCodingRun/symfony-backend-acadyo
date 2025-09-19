#!/bin/bash
set -e

echo "Attente de MongoDB..."
/usr/local/bin/wait-for-it.sh db_acadyo:27017 --timeout=60 --strict -- echo "MongoDB est prêt!"

echo "Préparation de l'application..."
# Créer tous les répertoires nécessaires
mkdir -p /var/www/html/var/cache /var/www/html/var/log
mkdir -p /var/www/html/var/cache/doctrine/odm/mongodb/{Hydrators,Proxies}

# Définir les permissions APRÈS la création des répertoires
chown -R www-data:www-data /var/www/html/var
chmod -R 775 /var/www/html/var

# Vérification des permissions (pour debug)
echo "Permissions du répertoire Hydrators :"
ls -la /var/www/html/var/cache/doctrine/odm/mongodb/ || echo "Répertoire pas encore créé"

if [ "$APP_ENV" != "prod" ]; then
    composer install --optimize-autoloader
    composer run-script auto-scripts || true
fi

# Clear cache APRÈS avoir défini les permissions
php bin/console cache:clear --env=$APP_ENV || true

# Vérifier à nouveau après le cache clear
echo "Permissions après cache:clear :"
ls -la /var/www/html/var/cache/doctrine/odm/mongodb/ || echo "Répertoire pas encore créé"

echo "Démarrage Apache..."
exec apache2-foreground
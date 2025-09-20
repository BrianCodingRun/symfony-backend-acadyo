#!/bin/bash
set -e

# Déterminer le service MongoDB selon l'environnement
if [ "$APP_ENV" = "test" ]; then
    MONGO_SERVICE="db_acadyo_test"
    DATABASE_NAME="db_acadyo_test"
else
    MONGO_SERVICE="db_acadyo"
    DATABASE_NAME="db_acadyo"
fi

echo "Attente de MongoDB ($MONGO_SERVICE)..."
/usr/local/bin/wait-for-it.sh $MONGO_SERVICE:27017 --timeout=60 --strict -- echo "MongoDB est prêt!"

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

if [ "$APP_ENV" = "dev" ]; then
    echo "Chargement des fixtures de développement..."
    php bin/console doctrine:fixtures:load --no-interaction || echo "Fixtures déjà chargées ou erreur ignorée"
fi

if [ "$APP_ENV" != "prod" ]; then
    composer install --optimize-autoloader
    composer run-script auto-scripts || true
fi

# Clear cache APRÈS avoir défini les permissions
php bin/console cache:clear --env=$APP_ENV || true

# Vérifier à nouveau après le cache clear
echo "Permissions après cache:clear :"
ls -la /var/www/html/var/cache/doctrine/odm/mongodb/ || echo "Répertoire pas encore créé"

# Comportement différent selon l'environnement
if [ "$APP_ENV" = "test" ]; then
    echo "Mode test - Lancement des tests..."
    echo "Chargement des fixtures..."
    php bin/console doctrine:mongodb:fixtures:load --env=test --no-interaction
    php bin/phpunit
    exit_code=$?
    echo "Tests terminés avec le code : $exit_code"
    exit $exit_code
else
    if [ "$APP_ENV" = "prod" ]; then
        echo "Vérification de l'utilisateur admin..."
        php bin/console app:create-admin-user --env=prod
    fi

    echo "Démarrage Apache..."
    exec apache2-foreground
fi
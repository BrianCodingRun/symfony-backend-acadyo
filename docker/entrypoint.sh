#!/bin/bash
set -e

# Extraire host et port de MONGODB_URL
MONGO_HOST=$(echo "$MONGODB_URL" | sed -E 's#mongodb://([^:/]+)(:[0-9]+)?.*#\1#')
MONGO_PORT=$(echo "$MONGODB_URL" | sed -E 's#mongodb://[^:/]+:([0-9]+).*#\1#')

# Si pas de port trouvé → 27017 par défaut
if [ -z "$MONGO_PORT" ] || ! [[ "$MONGO_PORT" =~ ^[0-9]+$ ]]; then
  MONGO_PORT=27017
fi

echo "Attente de MongoDB distant ($MONGO_HOST:$MONGO_PORT)..."
until nc -z "$MONGO_HOST" "$MONGO_PORT"; do
  echo "MongoDB non disponible, nouvel essai dans 2s..."
  sleep 2
done
echo "MongoDB est prêt!"

echo "Préparation de l'application..."
mkdir -p /var/www/html/var/cache /var/www/html/var/log
mkdir -p /var/www/html/var/cache/doctrine/odm/mongodb/{Hydrators,Proxies}

chown -R www-data:www-data /var/www/html/var
chmod -R ug+rwX /var/www/html/var

# Install dépendances en non-prod
if [ "$APP_ENV" != "prod" ]; then
  composer install --optimize-autoloader
  composer run-script auto-scripts || true
fi

# Cache clear
php bin/console cache:clear --env=$APP_ENV || true

# Mode dev
if [ "$APP_ENV" = "dev" ]; then
  echo "Chargement des fixtures de développement..."
  php bin/console doctrine:mongodb:fixtures:load --no-interaction || true
fi

# Mode test
if [ "$APP_ENV" = "test" ]; then
  echo "Chargement des fixtures de test..."
  php bin/console doctrine:mongodb:fixtures:load --env=test --no-interaction
  echo "Lancement des tests..."
  php bin/phpunit
  exit_code=$?
  echo "Tests terminés avec le code : $exit_code"
  exit $exit_code
fi

# Mode prod
if [ "$APP_ENV" = "prod" ]; then
  echo "Vérification de l'utilisateur admin..."
  php bin/console app:create-admin-user --env=prod || echo "Admin déjà existant"
fi

echo "Démarrage Apache..."
exec apache2-foreground

#!/bin/bash
set -e

#
# 1. Vérification MongoDB (host/port)
#

# Extraire la partie après @ (host:port/...)
MONGO_PART=$(echo "$MONGODB_URL" | awk -F'@' '{print $2}')

# Hôte = avant ":" ou "/"
MONGO_HOST=$(echo "$MONGO_PART" | cut -d: -f1 | cut -d/ -f1)

# Port = après ":" si présent, sinon vide
MONGO_PORT=$(echo "$MONGO_PART" | cut -d: -f2 | cut -d/ -f1)

# Port par défaut si vide ou invalide
if [ -z "$MONGO_PORT" ] || ! [[ "$MONGO_PORT" =~ ^[0-9]+$ ]]; then
  MONGO_PORT=27017
fi

echo "Attente de MongoDB distant ($MONGO_HOST:$MONGO_PORT)..."
until nc -z "$MONGO_HOST" "$MONGO_PORT"; do
  echo "MongoDB non disponible, nouvel essai dans 2s..."
  sleep 2
done
echo "MongoDB est prêt!"

#
# 2. Préparation Symfony (cache, logs, droits)
#

echo "Préparation de l'application..."
mkdir -p /var/www/html/var/cache /var/www/html/var/log
mkdir -p /var/www/html/var/cache/doctrine/odm/mongodb/{Hydrators,Proxies}

chown -R www-data:www-data /var/www/html/var
chmod -R 775 /var/www/html/var

#
# 3. Comportement selon environnement
#

if [ "$APP_ENV" = "test" ]; then
  echo "Chargement des fixtures de test..."
  php bin/console doctrine:mongodb:fixtures:load --no-interaction --env=test
  echo "Lancement des tests..."
  php bin/phpunit
  exit_code=$?
  echo "Tests terminés avec le code : $exit_code"
  exit $exit_code
fi

if [ "$APP_ENV" = "prod" ]; then
  php bin/console cache:clear --env=${APP_ENV}
  echo "Vérification de l'utilisateur admin..."
  php bin/console app:create-admin-user --env=${APP_ENV} || echo "Admin déjà existant"
fi

#
# 4. Lancement Apache en foreground
#

echo "Démarrage Apache..."
exec apache2-foreground

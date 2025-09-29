# Acadyo e-learning - API Backend

Une API REST moderne développée avec Symfony 7.3 et MongoDB pour gérer une plateforme e-learning complète avec gestion de classes, cours, devoirs.

[![CI/CD Pipeline](https://github.com/symfony-backend-acadyo/symfony-backend-acadyo/workflows/CI/CD%20Pipeline/badge.svg)](https://github.com/symfony-backend-acadyo/symfony-backend-acadyo/actions)

## 📋 Table des matières

-   [Fonctionnalités](#-fonctionnalités)
-   [Technologies](#-technologies-utilisées)
-   [Prérequis](#-prérequis)
-   [Installation](#-installation)
-   [Configuration](#-configuration)
-   [Architecture](#-architecture-du-projet)
-   [API Endpoints](#-api-endpoints)
-   [Tests](#-tests)
-   [CI/CD](#-cicd)
-   [Commandes utiles](#-commandes-utiles)
-   [Contribution](#-contribution)

## 🚀 Fonctionnalités

### Authentification & Utilisateurs

-   ✅ Inscription et connexion utilisateur
-   ✅ Authentification JWT sécurisée
-   ✅ Réinitialisation de mot de passe par email
-   ✅ Gestion des rôles (Admin, Teacher, Student)

### Gestion des classes (Classrooms)

-   ✅ Création de classes virtuelles par les professeurs
-   ✅ Code d'inscription unique pour chaque classe
-   ✅ Inscription/désinscription des étudiants
-   ✅ Liste des étudiants par classe
-   ✅ Gestion des permissions (professeur uniquement)

### Cours (Courses)

-   ✅ Création et gestion de supports de cours
-   ✅ Upload de fichiers (PDF, documents) via Cloudinary
-   ✅ Association cours-classe
-   ✅ Mise à jour et suppression de fichiers

### Devoirs (Assignments)

-   ✅ Création de devoirs avec instructions et date limite
-   ✅ Attribution à des étudiants spécifiques
-   ✅ Association aux classes

### Rendus de devoirs (DutyRendered)

-   ✅ Soumission de devoirs par les étudiants
-   ✅ Upload de fichiers de rendu
-   ✅ Notation et commentaires du professeur
-   ✅ Suivi des dates de soumission

### Inscriptions (Enrollments)

-   ✅ Inscription aux classes via code
-   ✅ Gestion des cours de l'étudiant
-   ✅ Liste des étudiants par classe
-   ✅ Retrait d'étudiants (professeur)

## 🛠️ Technologies utilisées

### Framework & Core

-   **Symfony 7.3** - Framework PHP moderne
-   **PHP 8.4** - Version récente de PHP
-   **Apache** - Serveur web
-   **Composer** - Gestionnaire de dépendances

### Base de données

-   **MongoDB 8.0** - Base de données NoSQL
-   **Doctrine MongoDB ODM 5.4** - Object Document Mapper

### Sécurité & Authentification

-   **LexikJWTAuthenticationBundle 3.1** - Authentification JWT
-   **Symfony Security Bundle** - Système de sécurité
-   **NelmioCorsBundle 2.5** - Gestion des requêtes cross-origin

### Services externes

-   **Cloudinary** - Stockage et gestion de fichiers (images, PDF)
-   **Google Mailer** - Envoi d'emails via Gmail

### Développement & Tests

-   **PHPUnit 11.5** - Framework de tests
-   **API Platform 4.1** - Framework API REST
-   **Symfony Maker Bundle** - Génération de code

### Infrastructure

-   **Docker** - Conteneurisation
-   **Docker Compose** - Orchestration multi-conteneurs
-   **GitHub Actions** - CI/CD automatisé

## 📦 Prérequis

### Avec Docker (Recommandé)

-   Docker 20.10+
-   Docker Compose 2.0+

### Sans Docker (Installation locale)

-   PHP 8.2+
-   MongoDB 8.0+
-   Composer 2.0+
-   Extensions PHP requises :
    -   `mongodb`
    -   `intl`
    -   `pdo`
    -   `zip`
    -   `ctype`
    -   `iconv`

## 🚀 Installation

### Option 1 : Installation avec Docker (Recommandé)

#### 1. Cloner le projet

```bash
git clone https://github.com/BrianCodingRun/symfony-backend-acadyo.git
cd symfony-backend-acadyo
```

#### 2. Créer le fichier .env

Copiez `.env.example` en `.env` et configurez vos variables :

```bash
cp .env.example .env
```

#### 3. Créer le réseau Docker externe

```bash
docker network create npm-network
```

#### 4. Démarrer l'application

```bash
# En production
APP_ENV=prod docker-compose up -d --build

# En développement
APP_ENV=dev docker-compose up -d --build
```

#### 5. Vérifier l'installation

```bash
# Voir les conteneurs actifs
docker-compose ps

# Voir les logs
docker-compose logs -f app_acadyo

# Tester l'API
curl http://localhost:8000/api
```

L'API sera accessible sur **http://localhost:8000**

### Option 2 : Installation locale

#### 1. Cloner le projet

```bash
git clone https://github.com/BrianCodingRun/symfony-backend-acadyo.git
cd symfony-backend-acadyo
```

#### 2. Installer les dépendances

```bash
composer install
```

#### 3. Configuration de MongoDB

Assurez-vous que MongoDB est démarré :

```bash
# Linux/Mac
sudo systemctl start mongodb

# Vérifier le statut
sudo systemctl status mongodb
```

#### 4. Configuration de l'environnement

Créez un fichier `.env.local` :

```env
APP_ENV=dev
APP_SECRET=votre-secret-key

MONGODB_URL=mongodb://localhost:27017
MONGODB_DB=acadyo
MONGODB_DB_TEST=acadyo_test

JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=votre-passphrase

CORS_ALLOW_ORIGIN=http://localhost:5173

MAILER_DSN=gmail://votre-email@gmail.com:app-password@default

CLOUDINARY_CLOUD_NAME=votre-cloud-name
CLOUDINARY_API_KEY=votre-api-key
CLOUDINARY_API_SECRET=votre-api-secret

ADMIN_NAME=Admin
ADMIN_EMAIL=admin@acadyo.com
ADMIN_PASSWORD=SecurePassword123!
```

#### 5. Générer les clés JWT

```bash
php bin/console lexik:jwt:generate-keypair
```

#### 6. Créer l'utilisateur administrateur

```bash
php bin/console app:create-admin-user
```

#### 7. Charger les fixtures (optionnel - pour dev/test)

```bash
php bin/console doctrine:mongodb:fixtures:load
```

#### 8. Démarrer le serveur

```bash
# Avec Symfony CLI
symfony server:start

# OU avec PHP built-in server
php -S localhost:8000 -t public/
```

## ⚙️ Configuration

### Variables d'environnement

Créez un fichier `.env` avec les variables suivantes :

```env
# Environnement
APP_ENV=prod                    # prod, dev, test
APP_SECRET=your-secret-key      # Clé secrète unique

# Base de données MongoDB
MONGODB_URL=mongodb://root:password@mongodb:27017/acadyo?authSource=admin
MONGODB_DB=acadyo
MONGODB_DB_TEST=acadyo_test

# JWT Authentication
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-jwt-passphrase

# CORS (Frontend URL)
CORS_ALLOW_ORIGIN=http://localhost:5173

# Email (Gmail)
MAILER_DSN=gmail://your-email@gmail.com:your-app-password@default

# Cloudinary (Stockage fichiers)
CLOUDINARY_CLOUD_NAME=your-cloud-name
CLOUDINARY_API_KEY=your-api-key
CLOUDINARY_API_SECRET=your-api-secret

# Administrateur initial
ADMIN_NAME=Administrateur
ADMIN_EMAIL=admin@acadyo.com
ADMIN_PASSWORD=ChangeMe123!
```

### Configuration Gmail

Pour utiliser Gmail pour l'envoi d'emails :

1. Allez dans votre compte Google
2. Activez la validation en 2 étapes
3. Générez un mot de passe d'application : [https://myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
4. Utilisez ce mot de passe dans `MAILER_DSN`

### Configuration Cloudinary

1. Créez un compte sur [Cloudinary](https://cloudinary.com/)
2. Récupérez vos identifiants dans le Dashboard
3. Configurez les variables dans `.env`

## 📁 Architecture du projet

```
symfony-backend-acadyo/
├── bin/                          # Exécutables (console, phpunit)
├── config/                       # Configuration Symfony
│   ├── packages/                 # Configuration des bundles
│   │   ├── api_platform.yaml
│   │   ├── doctrine_mongodb.yaml
│   │   ├── lexik_jwt_authentication.yaml
│   │   ├── nelmio_cors.yaml
│   │   └── security.yaml
│   ├── routes/                   # Routes
│   └── services.yaml             # Services
├── docker/                       # Configuration Docker
│   ├── apache/vhost.conf
│   └── entrypoint.sh
├── public/                       # Point d'entrée web
│   └── index.php
├── src/
│   ├── Command/                  # Commandes console
│   │   └── CreateAdminUserCommand.php
│   ├── Controller/               # Contrôleurs API
│   │   ├── AuthController.php           # Authentification
│   │   ├── ClassroomController.php      # Gestion classes
│   │   ├── CourseController.php         # Gestion cours
│   │   ├── DutyRenderedController.php   # Rendus devoirs
│   │   └── EnrollmentController.php     # Inscriptions classrooms
│   ├── Document/                 # Entités MongoDB
│   │   ├── Assignment.php               # Devoirs
│   │   ├── Classroom.php                # Classes
│   │   ├── Course.php                   # Cours
│   │   ├── DutyRendered.php             # Rendus
│   │   └── User.php                     # Utilisateurs
│   ├── Repository/               # Repositories MongoDB
│   ├── Service/                  # Services métier
│   │   └── CloudinaryService.php
│   ├── DataFixtures/             # Fixtures de test
│   │   └── UserFixtures.php
│   ├── EventListener/            # Event listeners
│   │   └── AuthenticationSuccessListener.php
│   └── Kernel.php
├── tests/                        # Tests automatisés
│   ├── Controller/
│   │   ├── AuthControllerTest.php
│   │   ├── ClassroomControllerTest.php
│   │   └── CourseControllerTest.php
│   └── bootstrap.php
├── var/                          # Cache et logs
├── .github/workflows/            # CI/CD
│   └── ci-cd.yml
├── docker-compose.yaml           # Orchestration Docker
├── docker-compose.test.yaml      # Tests Docker
├── Dockerfile
└── phpunit.dist.xml              # Configuration PHPUnit
```

## 🔌 API Endpoints

### Authentification

| Méthode | Endpoint                      | Description                   | Auth |
| ------- | ----------------------------- | ----------------------------- | ---- |
| POST    | `/api/login`                  | Connexion utilisateur         | Non  |
| POST    | `/api/register`               | Inscription utilisateur       | Non  |
| POST    | `/api/request-reset-password` | Demande réinitialisation      | Non  |
| POST    | `/api/reset-password`         | Réinitialisation mot de passe | Non  |

**Exemple de connexion :**

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@acadyo.com",
    "password": "SecurePassword123!"
  }'
```

**Réponse :**

```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
        "id": "67890abcdef123456",
        "name": "Admin",
        "email": "admin@acadyo.com",
        "roles": ["ROLE_ADMIN", "ROLE_TEACHER"]
    }
}
```

### Classrooms (Classes)

| Méthode | Endpoint               | Description          | Rôle requis |
| ------- | ---------------------- | -------------------- | ----------- |
| GET     | `/api/classrooms`      | Liste des classes    | Tous        |
| GET     | `/api/classrooms/{id}` | Détails d'une classe | Tous        |
| POST    | `/api/classroom`       | Créer une classe     | Teacher     |
| PUT     | `/api/classrooms/{id}` | Modifier une classe  | Teacher     |
| DELETE  | `/api/classrooms/{id}` | Supprimer une classe | Teacher     |

**Exemple de création de classe :**

```bash
curl -X POST http://localhost:8000/api/classroom \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Mathématiques Avancées",
    "description": "Cours de mathématiques niveau terminale"
  }'
```

### Courses (Cours)

| Méthode | Endpoint                 | Description        | Rôle requis |
| ------- | ------------------------ | ------------------ | ----------- |
| GET     | `/api/courses`           | Liste des cours    | Tous        |
| GET     | `/api/courses/{id}`      | Détails d'un cours | Tous        |
| POST    | `/api/courses`           | Créer un cours     | Teacher     |
| POST    | `/api/courses/{id}`      | Modifier un cours  | Teacher     |
| DELETE  | `/api/courses/{id}`      | Supprimer un cours | Teacher     |
| DELETE  | `/api/courses/{id}/file` | Supprimer fichier  | Teacher     |

**Exemple de création de cours avec fichier :**

```bash
curl -X POST http://localhost:8000/api/courses \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "title=Chapitre 1: Introduction" \
  -F "content=Contenu du cours..." \
  -F "classroom=67890classroom123" \
  -F "file=@/path/to/document.pdf"
```

### Assignments (Devoirs)

| Méthode | Endpoint                | Description         | Rôle requis |
| ------- | ----------------------- | ------------------- | ----------- |
| GET     | `/api/assignments`      | Liste des devoirs   | Tous        |
| GET     | `/api/assignments/{id}` | Détails d'un devoir | Tous        |
| POST    | `/api/assignments`      | Créer un devoir     | Teacher     |
| PUT     | `/api/assignments/{id}` | Modifier un devoir  | Teacher     |
| DELETE  | `/api/assignments/{id}` | Supprimer un devoir | Teacher     |

### Duty Rendered (Rendus)

| Méthode | Endpoint                  | Description         | Rôle requis |
| ------- | ------------------------- | ------------------- | ----------- |
| GET     | `/api/dutysRendered`      | Liste des rendus    | Tous        |
| GET     | `/api/dutysRendered/{id}` | Détails d'un rendu  | Tous        |
| POST    | `/api/dutyRendered`       | Soumettre un devoir | Student     |
| POST    | `/api/dutyRendered/{id}`  | Modifier un rendu   | Teacher     |
| DELETE  | `/api/dutysRendered/{id}` | Supprimer un rendu  | Teacher     |

### Enrollments (Inscriptions)

| Méthode | Endpoint                                                    | Description          | Rôle requis |
| ------- | ----------------------------------------------------------- | -------------------- | ----------- |
| POST    | `/api/enrollment/join`                                      | Rejoindre une classe | Student     |
| DELETE  | `/api/enrollment/leave/{id}`                                | Quitter une classe   | Student     |
| GET     | `/api/enrollment/my-classrooms`                             | Mes classes          | Student     |
| GET     | `/api/enrollment/classroom/{id}/students`                   | Liste étudiants      | Tous        |
| DELETE  | `/api/enrollment/classroom/{id}/remove-student/{studentId}` | Retirer étudiant     | Teacher     |

**Exemple d'inscription à une classe :**

```bash
curl -X POST http://localhost:8000/api/enrollment/join \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "4A3F1C"
  }'
```

## 🧪 Tests

Le projet inclut des tests automatisés avec PHPUnit.

### Lancer les tests

```bash
# Avec Docker
docker-compose -f docker-compose.test.yaml up --build --abort-on-container-exit

# Localement
php bin/phpunit

# Tests avec couverture de code
php bin/phpunit --coverage-html coverage/
```

### Tests disponibles

-   ✅ **AuthControllerTest** : Authentification, inscription, réinitialisation mot de passe
-   ✅ **ClassroomControllerTest** : CRUD des classes, permissions
-   ✅ **CourseControllerTest** : Gestion des cours et fichiers

### Structure des tests

```
tests/
├── Controller/
│   ├── AuthControllerTest.php          # 8 tests
│   ├── ClassroomControllerTest.php     # 9 tests
│   └── CourseControllerTest.php        # 20+ tests
├── Controller/fixtures/
│   └── test-file.txt
└── bootstrap.php
```

## 🔄 CI/CD

Le projet utilise GitHub Actions pour l'intégration et le déploiement continus.

### Pipeline CI/CD

Le workflow `.github/workflows/ci-cd.yml` effectue :

1. **Tests automatiques** sur chaque push/PR

    - Lancement de tous les tests PHPUnit
    - Vérification de la qualité du code

2. **Déploiement automatique** sur la branche `main`

    - Connexion SSH au serveur
    - Pull du code
    - Rebuild des containers Docker
    - Vérification du déploiement

3. **Notifications** de l'état du déploiement

### Configuration du déploiement

Ajoutez ces secrets dans votre repository GitHub :

-   `SERVER_HOST` : Adresse IP du serveur
-   `SERVER_USER` : Nom d'utilisateur SSH
-   `SERVER_PASSWORD` : Mot de passe SSH (ou utilisez une clé SSH)

## 🔧 Commandes utiles

### Commandes Docker

```bash
# Démarrer l'application
docker-compose up -d

# Arrêter l'application
docker-compose down

# Voir les logs
docker-compose logs -f app_acadyo

# Rebuild après modification du Dockerfile
docker-compose up -d --build

# Exécuter une commande dans le container
docker-compose exec app_acadyo php bin/console <commande>

# Accéder au shell du container
docker-compose exec app_acadyo bash
```

### Commandes Symfony

```bash
# Créer l'utilisateur admin
php bin/console app:create-admin-user

# Vider le cache
php bin/console cache:clear

# Lister les routes
php bin/console debug:router

# Charger les fixtures (dev/test)
php bin/console doctrine:mongodb:fixtures:load

# Générer les clés JWT
php bin/console lexik:jwt:generate-keypair
```

### Commandes MongoDB (dans le container)

```bash
# Se connecter à MongoDB
docker-compose exec mongodb mongosh -u root -p rootpassword

# Afficher les bases de données
show dbs

# Utiliser une base
use acadyo

# Lister les collections
show collections

# Voir des documents
db.user.find().pretty()
```

## 🐛 Débogage

### Problèmes courants

**1. Erreur de connexion MongoDB**

```bash
# Vérifier que MongoDB est démarré
docker-compose ps

# Vérifier les logs MongoDB
docker-compose logs mongodb
```

**2. Erreur JWT**

```bash
# Régénérer les clés JWT
docker-compose exec app_acadyo php bin/console lexik:jwt:generate-keypair

# Vérifier les permissions
docker-compose exec app_acadyo ls -la config/jwt/
```

**3. Erreur de permissions**

```bash
# Corriger les permissions
docker-compose exec app_acadyo chown -R www-data:www-data /var/www/html/var
docker-compose exec app_acadyo chmod -R 775 /var/www/html/var
```

**4. Erreur CORS**

Vérifiez la configuration dans `config/packages/nelmio_cors.yaml` et la variable `CORS_ALLOW_ORIGIN` dans `.env`.

## 📚 Documentation supplémentaire

-   [Documentation Symfony](https://symfony.com/doc/current/index.html)
-   [API Platform](https://api-platform.com/docs/)
-   [MongoDB ODM](https://www.doctrine-project.org/projects/mongodb-odm.html)
-   [Cloudinary PHP](https://cloudinary.com/documentation/php_integration)

## 📄 Licence

Ce projet est sous licence propriétaire. Tous droits réservés.

## 👥 Auteurs

-   **COUPAMA Brian** - _Développement initial_ - [GitHub](https://github.com/BrianCodingRun)

## 🙏 Remerciements

-   Équipe Symfony
-   Communauté API Platform
-   Tous les contributeurs

---

**Note** : Ce projet est en développement actif. N'hésitez pas à ouvrir une issue pour signaler des bugs ou proposer des améliorations.

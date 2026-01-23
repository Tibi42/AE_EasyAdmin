# Auxilia E-commerce

Application e-commerce développée avec Symfony 7.x permettant la gestion complète d'une boutique en ligne avec administration.

## 🚀 Fonctionnalités principales

### Côté Client

- **Catalogue de produits** : Affichage, recherche, filtrage par catégorie et tri
- **Gestion du panier** : Ajout, modification, suppression de produits avec persistance entre sessions
- **Paiement sécurisé** : Intégration **Stripe Checkout** pour les transactions par carte bancaire
- **Gestion des commandes** : Visualisation de l'historique et détails des commandes
- **Authentification** : Inscription, connexion, réinitialisation de mot de passe
- **Profil utilisateur** : Gestion des informations personnelles et historique des commandes
- **Newsletter** : Inscription dynamique via AJAX
- **Témoignages** : Dépôt d'avis et notation (étoiles) sur la boutique

### Administration

- **Tableau de bord** : Statistiques globales (CA, produits, utilisateurs, commandes, avis)
- **Gestion des produits** : CRUD complet avec gestion du stock et des catégories
- **Gestion des utilisateurs** : Visualisation, édition, réinitialisation de mot de passe, activation/désactivation de comptes
- **Gestion des commandes** : Suivi complet avec filtrage par statut et **gestion de la livraison** (transporteur, numéro de suivi)
- **Modération** : Validation des témoignages clients avant publication
- **Newsletter** : Liste et gestion des abonnés

### ⚡ Expérience & Tech

- **Hotwire Turbo & Stimulus** : Navigation ultra-rapide sans rechargement de page (Proche d'une SPA)
- **Modales Dynamiques** : Consultation de produits fluide et interactive
- **Design Responsive** : Optimisation "Mobile-First" pour tous les écrans

## 📋 Prérequis

- PHP 8.2 ou supérieur
- Composer 2.x
- MySQL 8.0 ou supérieur (ou MariaDB 10.3+)
- Symfony CLI (optionnel)

## 🛠️ Installation

1. **Cloner le projet**

```bash
git clone <url-du-repo>
cd Auxilia-Ecommerce
```

1. **Installer les dépendances**

```bash
composer install
```

1. **Configurer la base de données**

```bash
# Créer le fichier .env.local et configurer la connexion
DATABASE_URL="mysql://user:password@127.0.0.1:3306/auxilia_ecommerce?serverVersion=8.0&charset=utf8mb4"
```

1. **Créer la base de données et exécuter les migrations**

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

1. **Charger les données de test (optionnel)**

```bash
php bin/console doctrine:fixtures:load
```

1. **Lancer le serveur de développement**

```bash
symfony server:start
# ou
php -S localhost:8000 -t public
```

## 📁 Structure du projet

```text
src/
├── Controller/           # Contrôleurs de l'application
│   ├── Admin/           # Contrôleurs d'administration
│   ├── CartController   # Gestion du panier
│   ├── OrderController  # Gestion des commandes
│   ├── ProductController # Catalogue produits
│   └── ...
├── Entity/              # Entités Doctrine (modèles)
│   ├── User.php         # Utilisateur
│   ├── Product.php      # Produit
│   ├── Order.php        # Commande
│   └── OrderItem.php    # Article de commande
├── Repository/          # Repositories Doctrine
├── Service/             # Services métier
│   └── CartService.php  # Service de gestion du panier
├── Form/                # Formulaires Symfony
├── Security/            # Sécurité
│   └── UserChecker.php  # Vérification des comptes désactivés
└── EventSubscriber/     # Abonnés d'événements
    └── LoginCartSubscriber.php # Synchronisation du panier à la connexion

templates/
├── admin/               # Templates d'administration
├── order/               # Templates des commandes
├── product/             # Templates des produits
└── ...
```

## 🔐 Sécurité

### Authentification

- Authentification par email/mot de passe
- Réinitialisation de mot de passe via email
- Protection CSRF sur tous les formulaires
- Validation des comptes désactivés (UserChecker)

### Autorisations

- Routes publiques : Catalogue, panier, pages statiques
- Routes authentifiées : Profil, commandes
- Routes admin : Toutes les routes `/admin/*` nécessitent le rôle `ROLE_ADMIN`

### Statuts des comptes

- Les comptes peuvent être activés/désactivés par l'administrateur
- Les comptes désactivés ne peuvent pas se connecter
- Les administrateurs ne peuvent pas être désactivés

## 🗄️ Base de données

### Principales entités

**User** : Utilisateurs

- email, password, roles
- Informations personnelles (firstName, lastName, phone, address, etc.)
- Panier persistant (cart)
- Statut actif/inactif (isActive)

**Product** : Produits

- name, description, price, stock
- Catégorie associée
- Images (chemin)

**Order** : Commandes

- Utilisateur associé
- Statut (pending, paid, confirmed, shipped, delivered, cancelled)
- Total, date
- Informations de livraison (carrier, trackingNumber, shippedAt)
- Identifiants de transaction Stripe
- Relation OneToMany avec OrderItem

**OrderItem** : Articles de commande

- Produit associé
- Quantité, prix unitaire, total
- Snapshots sécurisés du produit (nom et prix persistés au moment de l'achat)

## 🔧 Services

### CartService

Gère la logique métier du panier :

- Stockage en session pour les utilisateurs non connectés
- Persistance en base de données pour les utilisateurs connectés
- Synchronisation automatique lors de la connexion (LoginCartSubscriber)

### UserChecker

Vérifie l'état des comptes lors de l'authentification :

- Empêche la connexion des comptes désactivés
- Affiche un message d'erreur approprié

## 📝 Commandes utiles

```bash
# Créer une migration
php bin/console make:migration

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Charger les fixtures
php bin/console doctrine:fixtures:load

# Vider le cache
php bin/console cache:clear

# Voir les routes
php bin/console debug:router

# Voir les services
php bin/console debug:container
```

## 🚀 Optimisations de Performance

### Configuration PHP (php.ini)

Pour des performances optimales en production, configurez OPcache :

```ini
; OPcache - Obligatoire pour de bonnes performances
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0  ; Mettre à 1 en dev
opcache.revalidate_freq=0
opcache.preload=/chemin/vers/projet/config/preload.php
opcache.preload_user=www-data

; Realpath cache
realpath_cache_size=4096K
realpath_cache_ttl=600
```

### Mise en cache Doctrine

Le projet utilise plusieurs niveaux de cache :
- **Metadata Cache** : Cache des définitions d'entités
- **Query Cache** : Cache des requêtes DQL parsées  
- **Result Cache** : Cache des résultats de requêtes fréquentes

### Index Base de Données

Des index ont été ajoutés sur les colonnes fréquemment utilisées :
- `Product` : category, isFeatured, price, name
- `Order` : status, dateat, user_id
- `Category` : name, slug

Après modification des entités, générez la migration :

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Commandes de préparation pour la production

```bash
# Vider et réchauffer le cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Compiler les assets
php bin/console asset-map:compile

# Dumper l'autoloader optimisé
composer dump-autoload --optimize --classmap-authoritative
```

## 🧪 Tests

Les tests peuvent être exécutés avec PHPUnit :

```bash
php bin/phpunit
```

## 📚 Documentation additionnelle

- [Documentation Symfony](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/index.html)
- [Twig](https://twig.symfony.com/doc/)

## 👥 Auteurs

Développé pour Auxilia E-commerce

## 📄 Licence

Propriétaire - Tous droits réservés

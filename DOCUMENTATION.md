# Documentation Technique - Auxilia E-commerce

Ce document fournit une vue d'ensemble technique de l'application **Auxilia E-commerce**, détaillant ses composants, sa logique métier et sa structure de sécurité.

---

## 🏗 1. Architecture Globale

L'application est bâtie sur le framework **Symfony 8.x** en suivant une architecture MVC (Modèle-Vue-Contrôleur) classique, enrichie par des services métier spécialisés.

- **Backend** : PHP 8.4+, Symfony (Core, Security, Doctrine, Twig, KnpPaginator, Fixtures, Rate Limiter).
- **Base de données** : MySQL/MariaDB (ORM Doctrine).
- **Frontend** : Twig, AssetMapper (sans Webpack), Stimulus & Turbo (HMR-like experience).
- **Style** : Vanilla CSS 3 (Layouts Flexbox/Grid, variables CSS).
- **Paiement** : Stripe (Checkout, Webhooks).

---

## 🗄 2. Modèle de Données (Entités)

L'application s'articule autour de **7 entités** : User, Product, Order, OrderItem, Category, Newsletter, Testimonial.

### `User`

- Gère l'authentification, les profils et la réinitialisation de mot de passe.
- **Attributs clés** : `email` (identifiant), `roles`, `password` (haché), `cart` (array/JSON pour la persistance), `resetToken` / `resetTokenExpiresAt` (réinitialisation MDP), `isActive`, et champs de profil (firstName, lastName, phone, address, postalCode, city, country).
- **Sécurité** : Système d'activation/désactivation (`isActive`) géré par un `UserChecker`.

### `Product`

- Représente les articles du catalogue.
- **Champs importants** : `price`, `stock`, `imageName`, `category` (chaîne), `isFeatured` (mise en avant page d'accueil).
- La catégorie est stockée en chaîne de caractères ; l'entité `Category` est utilisée séparément (admin, menus).

### `Order` & `OrderItem`

- **Order** : En-tête de commande rattachée à un utilisateur, avec un statut (`pending`, `paid`, `shipped`, etc.) et `stripeSessionId` pour le paiement Stripe.
- **OrderItem** : Détail de chaque ligne de commande.
- *Note technique* : Le nom et le prix du produit sont copiés dans `OrderItem` lors de la validation pour éviter que le changement futur d'un produit ne modifie les factures passées.

### Autres entités

- **Category** : Catégories de produits (nom, slug) pour l’admin et les listes.
- **Newsletter** : Inscriptions à la newsletter (email, actif).
- **Testimonial** : Avis clients (nom, email, note, contenu, approuvé).

---

## ⚙️ 3. Logique Métier & Services

### 🛒 Gestion du Panier (`CartService`)

Le `CartService` est le cœur de l'expérience d'achat. Il gère :

1. **Stockage hybride** : Utilise la session pour la rapidité et la base de données (`User::cart`) pour la persistance long terme.
2. **Opérations** : `add()`, `remove()`, `deleteAll()`, `clear()`.
3. **Calculs** : Somme des quantités (`getQuantitySum()`) et montant total (`getTotal()`).

### 🔄 Synchronisation du Panier (`LoginCartSubscriber`)

Un abonné aux événements de connexion (`SecurityEvents::INTERACTIVE_LOGIN`) permet de fusionner ou de restaurer le panier stocké en base de données dès qu'un utilisateur se connecte.

### 💳 Paiement (`StripeService`, `OrderController`, `StripeWebhookController`)

- **Stripe Checkout** : Création de sessions de paiement, redirection vers Stripe, retour et mise à jour du statut des commandes.
- **Webhooks** : Traitement des événements Stripe (`checkout.session.completed`, `payment_intent.succeeded`) pour fiabiliser le passage en `paid` même en cas de fermeture du navigateur.

---

## 🛡 4. Sécurité & Protection

### 🔑 Authentification & Autorisation

- **Pare-feu** : Défini dans `security.yaml`.
- **Hiérarchie** :
  - `ROLE_USER` : Accès au profil et historique des commandes.
  - `ROLE_ADMIN` : Accès complet au dashboard et à la gestion.
- **UserChecker** : Intercepte les tentatives de connexion pour bloquer les comptes marqués comme désactivés.

### 🛡 Protections Intégrées

- **CSRF** : Protection active sur tous les formulaires et actions critiques (ex: suppression au panier).
- **En-têtes HTTP (`SecurityHeadersSubscriber`)** : Ajout automatique de `X-Frame-Options`, `X-Content-Type-Options` et `Content-Security-Policy` pour prévenir les attaques XSS et le clickjacking.
- **Validation** : Contraintes de validation strictes sur les entités (Assert) et les formulaires.
- **Rate Limiter** : Limitation des requêtes sur l’inscription et la réinitialisation de mot de passe pour limiter les abus.
- **Réinitialisation de mot de passe** : Token temporaire dans `User` (`resetToken`, `resetTokenExpiresAt`), flux dédié (`ResetPasswordController`) et envoi d’emails.

---

## 🖥 5. Frontend & UX

### ⚡️ Rapidité de navigation (Turbo)

L'utilisation de **@hotwired/turbo** permet des transitions de pages instantanées sans rechargement complet du DOM, offrant une expérience proche d'une SPA (Single Page Application).

### 🎨 Design System

- **Responsive** : Design "Mobile-First" utilisant CSS Grid et Flexbox.
- **Modales Dynamiques** : Gérées par des contrôleurs **Stimulus** (`modal_controller.js`) permettant d'afficher les détails des produits sans changer de page.

---

## 🛠 6. Espace Administration

L'interface d'administration est isolée sous le préfixe `/admin` (EasyAdmin) :

- **Dashboard** : Statistiques en temps réel (ventes, CA, produits, utilisateurs, commandes, témoignages, newsletter, stocks critiques).
- **CRUD Produits** : Gestion complète avec upload d'images sécurisé (slugification, vérification des types MIME).
- **CRUD Utilisateurs** : Gestion des comptes, rôles, activation/désactivation, mot de passe.
- **CRUD Commandes** : Suivi du cycle de vie (statut, détails de livraison, transporteur, numéro de suivi).
- **CRUD Catégories** : Gestion des catégories (nom, slug).
- **CRUD Témoignages** : Modération des avis (approbation, note, contenu).
- **CRUD Newsletter** : Liste et gestion des abonnés.

---

## 🚀 7. Guide de Développement

### Installer l'environnement

```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load  # Pour avoir des données de test
```

### Qualité du Code

- Les contrôleurs doivent rester légers (**Thin Controllers**).
- La logique métier complexe doit être déportée dans des **Services**.
- Utilisez les **Fixtures** pour tester les scénarios de bord (stock vide, paniers volumineux).

---

*Document mis à jour le : 24/01/2026 - Équipe de Développement Auxilia.*

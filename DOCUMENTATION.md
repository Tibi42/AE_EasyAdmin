# Documentation Technique - Auxilia E-commerce

Ce document fournit une vue d'ensemble technique de l'application **Auxilia E-commerce**, détaillant ses composants, sa logique métier et sa structure de sécurité.

---

## 🏗 1. Architecture Globale

L'application est bâtie sur le framework **Symfony 7.x** en suivant une architecture MVC (Modèle-Vue-Contrôleur) classique, enrichie par des services métier spécialisés.

- **Backend** : PHP 8.2+, Symfony (Core, Security, Doctrine, Twig, Paginator, Fixtures).
- **Base de données** : MySQL/MariaDB (ORM Doctrine).
- **Frontend** : Twig, AssetMapper (sans Webpack), Stimulus & Turbo (HMR-like experience).
- **Style** : Vanilla CSS 3 (Layouts Flexbox/Grid, variables CSS).

---

## 🗄 2. Modèle de Données (Entités)

L'application s'articule autour de 5 entités principales :

### `User`

- Gère l'authentification et les profils.
- **Attributs clés** : `email` (identifiant), `roles`, `password` (haché), `cart` (JSON/Array pour la persistance).
- **Sécurité** : Intègre un système d'activation/désactivation (`isActive`) géré par un `UserChecker`.

### `Product`

- Représente les articles du catalogue.
- **Champs importants** : `price`, `stock`, `imageName`, `category`.
- Les catégories sont stockées sous forme de texte simple ou via une entité dédiée pour le filtrage dynamique.

### `Order` & `OrderItem`

- **Order** : En-tête de commande rattachée à un utilisateur, avec un statut (`paid`, `shipped`, etc.).
- **OrderItem** : Détail de chaque ligne de commande.
- *Note technique* : Le nom et le prix du produit sont copiés dans `OrderItem` lors de la validation pour éviter que le changement futur d'un produit ne modifie les factures passées.

---

## ⚙️ 3. Logique Métier & Services

### 🛒 Gestion du Panier (`CartService`)

Le `CartService` est le cœur de l'expérience d'achat. Il gère :

1. **Stockage hybride** : Utilise la session pour la rapidité et la base de données (`User::cart`) pour la persistance long terme.
2. **Opérations** : `add()`, `remove()`, `deleteAll()`, `clear()`.
3. **Calculs** : Somme des quantités et montant total HT/TTC.

### 🔄 Synchronisation du Panier (`LoginCartSubscriber`)

Un abonné aux événements de connexion (`SecurityEvents::INTERACTIVE_LOGIN`) permet de fusionner ou de restaurer le panier stocké en base de données dès qu'un utilisateur se connecte.

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

---

## 🖥 5. Frontend & UX

### ⚡️ Rapidité de navigation (Turbo)

L'utilisation de **@hotwired/turbo** permet des transitions de pages instantanées sans rechargement complet du DOM, offrant une expérience proche d'une SPA (Single Page Application).

### 🎨 Design System

- **Responsive** : Design "Mobile-First" utilisant CSS Grid et Flexbox.
- **Modales Dynamiques** : Gérées par des contrôleurs **Stimulus** (`modal_controller.js`) permettant d'afficher les détails des produits sans changer de page.

---

## 🛠 6. Espace Administration

L'interface d'administration est isolée sous le préfixe `/admin` :

- **Dashboard** : Statistiques en temps réel sur les ventes, les stocks critiques et les nouveaux utilisateurs.
- **CRUD Produits** : Gestion complète avec upload d'images sécurisé (slugification des noms de fichiers, vérification des types MIME).
- **Gestion Commandes** : Suivi du cycle de vie des commandes (changement de statut, détails de livraison).

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

*Document généré le : 17/01/2026 - Équipe de Développement Auxilia.*

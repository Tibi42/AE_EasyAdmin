# Fixtures - Données de test

Ce dossier contient les fixtures pour peupler la base de données avec des données de test réalistes pour l'e-commerce de vins.

## Fixtures disponibles

### UserFixtures
Crée des utilisateurs de test avec différents rôles :
- **Admin** : `admin@auxilia-ecommerce.com` / `admin123` (ROLE_ADMIN)
- **Utilisateurs Clients** :
  - `user1@example.com` / `user123`
  - `user2@example.com` / `user123`
  - `marie.dupont@example.com` / `password123`
  - `jean.martin@example.com` / `password123`

### ProductFixtures
Génère le catalogue de produits (vins et épicerie fine) :
- **5 catégories** : Vins Rouges, Vins Blancs, Vins Rosés, Champagnes & Bulles, Épicerie Fine.
- **13 produits** initiaux avec :
  - Noms et descriptions détaillées (cépages, notes de dégustation)
  - Prix et stocks réalistes
  - Images générées dynamiquement via Picsum

### OrderFixtures
*Dépend de UserFixtures et ProductFixtures.*

Génère des commandes de test pour chaque utilisateur :
- **1 à 3 commandes** par utilisateur.
- **1 à 5 articles** par commande.
- Statuts variés : `pending`, `confirmed`, `shipped`, `delivered`, `cancelled`.
- Dates aléatoires sur les 30 derniers jours.

### AppFixtures
Fixture principale qui orchestre le chargement en s'appuyant sur les dépendances définies dans les autres classes.

## Utilisation

Pour charger toutes les fixtures (efface et remplace les données existantes) :

```bash
php bin/console doctrine:fixtures:load
```

> [!WARNING]
> Cette commande supprime **toutes** les données existantes en base avant d'insérer les fixtures.

## Structure des données

### Catégories et Produits
- **Vins Rouges** : Château Grand Terroir, Pinot Noir, Syrah, Bordeaux Réserve.
- **Vins Blancs** : Chardonnay, Sauvignon Blanc, Grand Cru Montagne Bleue.
- **Vins Rosés** : Rosé de Provence, Gris de Gris.
- **Champagnes & Bulles** : Champagne Héritage, Crémant de Loire.
- **Épicerie Fine** : Huile d'Olive, Vinaigre de Vin Vieux.




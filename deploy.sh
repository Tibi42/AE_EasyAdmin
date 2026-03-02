#!/bin/bash
# =============================================================================
# Script de déploiement — AE EasyAdmin (Symfony 8)
# Usage : bash deploy.sh
# =============================================================================

set -e  # Arrête le script en cas d'erreur

# --- Couleurs ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

APP_DIR="/var/www/ae_easyadmin"
PHP="php8.4"
COMPOSER="composer"

log()    { echo -e "${BLUE}[DEPLOY]${NC} $1"; }
success(){ echo -e "${GREEN}[OK]${NC} $1"; }
warn()   { echo -e "${YELLOW}[WARN]${NC} $1"; }
error()  { echo -e "${RED}[ERREUR]${NC} $1"; exit 1; }

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}   Déploiement AE EasyAdmin             ${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Vérifier qu'on est dans le bon répertoire
cd "$APP_DIR" || error "Répertoire $APP_DIR introuvable"

# --- 1. Récupérer le code ---
log "Récupération du code depuis GitHub..."
git pull origin main
success "Code mis à jour ($(git log -1 --format='%h — %s'))"

# --- 2. Dépendances Composer ---
log "Installation des dépendances Composer..."
$COMPOSER install --no-dev --optimize-autoloader --no-interaction
success "Dépendances installées"

# --- 3. Compiler les variables d'environnement ---
log "Compilation des variables d'environnement..."
$COMPOSER dump-env prod
success "Environnement compilé"

# --- 4. Cache ---
log "Nettoyage du cache..."
$PHP bin/console cache:clear --env=prod --no-warmup
$PHP bin/console cache:warmup --env=prod
success "Cache régénéré"

# --- 5. Migrations BDD ---
log "Vérification des migrations..."
PENDING=$($PHP bin/console doctrine:migrations:status --env=prod | grep "New Migrations" | awk '{print $NF}')
if [ "$PENDING" != "0" ] && [ -n "$PENDING" ]; then
    warn "$PENDING migration(s) en attente, exécution..."
    $PHP bin/console doctrine:migrations:migrate --no-interaction --env=prod
    success "Migrations appliquées"
else
    success "Aucune migration en attente"
fi

# --- 6. Assets ---
log "Compilation des assets..."
$PHP bin/console importmap:install --env=prod
$PHP bin/console asset-map:compile --env=prod
success "Assets compilés"

# --- 7. Permissions ---
log "Mise à jour des permissions..."
sudo chown -R www-data:www-data var/ public/assets/
sudo chmod -R 775 var/
success "Permissions mises à jour"

# --- 8. Redémarrer PHP-FPM ---
log "Redémarrage de PHP-FPM..."
sudo systemctl reload php8.4-fpm
success "PHP-FPM rechargé"

# --- Résumé ---
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}   Déploiement terminé avec succès !    ${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "  Commit : $(git log -1 --format='%h')"
echo -e "  Branch : $(git branch --show-current)"
echo -e "  Date   : $(date '+%d/%m/%Y %H:%M:%S')"
echo ""

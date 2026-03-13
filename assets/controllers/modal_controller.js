// Contrôleur Stimulus : modale produit
// Gère l'affichage de la modale de détail produit, la gestion des quantités et l'ajout au panier
import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    // Cibles HTML : dialogue, titre, description, prix, lien panier, image, placeholder, feedback et quantité
    static targets = ["dialog", "title", "description", "price", "addToCartLink", "image", "placeholder", "feedback", "quantity"];

    // Ouvre la modale et peuple ses champs avec les données du produit cliqué
    open(event) {
        event.preventDefault();
        const data = event.currentTarget.dataset;

        this.titleTarget.textContent = data.name;
        this.descriptionTarget.textContent = data.description || "Aucune description disponible pour ce produit.";
        this.priceTarget.textContent = data.price + " €";
        this.addToCartLinkTarget.setAttribute('href', data.addToCartUrl);

        // Affiche l'image du produit ou le placeholder si aucune image n'est disponible
        if (data.image) {
            this.imageTarget.src = data.image;
            this.imageTarget.alt = data.name;
            this.imageTarget.classList.remove('d-none');
            this.placeholderTarget.classList.add('d-none');
        } else {
            this.imageTarget.classList.add('d-none');
            this.placeholderTarget.classList.remove('d-none');
        }

        // Réinitialise la quantité à 1 à chaque ouverture
        if (this.hasQuantityTarget) {
            this.quantityTarget.value = 1;
        }
        this._hideFeedback();

        Modal.getOrCreateInstance(this.dialogTarget).show();
    }

    // Empêche la propagation de l'événement (utile pour les clics dans la modale)
    stopPropagation(event) {
        event.stopPropagation();
    }

    // Augmente la quantité sélectionnée (maximum : 99)
    increaseQuantity(event) {
        event.preventDefault();
        event.stopPropagation();
        if (!this.hasQuantityTarget) return;
        const current = parseInt(this.quantityTarget.value, 10) || 1;
        this.quantityTarget.value = Math.min(99, current + 1);
    }

    // Diminue la quantité sélectionnée (minimum : 1)
    decreaseQuantity(event) {
        event.preventDefault();
        event.stopPropagation();
        if (!this.hasQuantityTarget) return;
        const current = parseInt(this.quantityTarget.value, 10) || 1;
        this.quantityTarget.value = Math.max(1, current - 1);
    }

    // Ajoute le produit au panier via une requête AJAX (avec la quantité choisie)
    async addToCart(event) {
        event.preventDefault();
        event.stopPropagation();

        const url = this.addToCartLinkTarget.getAttribute('href');
        if (!url || url === '#') return;

        const btn = event.currentTarget;
        this._setLoading(btn, true);
        this._hideFeedback();

        // Calcule la quantité en la bornant entre 1 et 99
        const qty = this.hasQuantityTarget
            ? Math.max(1, Math.min(99, parseInt(this.quantityTarget.value, 10) || 1))
            : 1;

        try {
            const body = new URLSearchParams();
            body.append('quantity', qty);

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body.toString(),
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const text = await response.text();
            let data = {};
            try {
                data = this._safeJsonParse(text);
            } catch (parseErr) {
                console.warn('Réponse non JSON lors de la mise à jour du panier, utilisation du fallback.', parseErr, text);
            }

            this._showFeedback('success', data.message || 'Quantité mise à jour dans le panier !');

            if (typeof data.count === 'number') {
                this._updateBadge(data.count);
            } else {
                this._incrementBadgeFallback(qty);
            }
        } catch (err) {
            console.error(`Erreur lors de la mise à jour du panier :`, err);
            this._showFeedback('danger', 'Une erreur est survenue. Merci de réessayer.');
        } finally {
            this._setLoading(btn, false);
        }
    }

    // Ajoute rapidement 1 unité du produit au panier sans ouvrir la modale
    async quickAdd(event) {
        event.preventDefault();
        event.stopPropagation();

        const link = event.currentTarget;
        const url = link.getAttribute('href');
        if (!url || url === '#') return;

        link.classList.add('disabled');
        const original = link.innerHTML; // Sauvegarde le contenu original du bouton

        try {
            const response = await fetch(`${url}?quantity=1`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const text = await response.text();
            let data = {};
            try {
                data = this._safeJsonParse(text);
            } catch (parseErr) {
                console.warn('Réponse non JSON lors de l\'ajout rapide, utilisation du fallback.', parseErr, text);
            }

            if (typeof data.count === 'number') {
                this._updateBadge(data.count);
            } else {
                this._incrementBadgeFallback(1);
            }

            link.innerHTML = '<i class="fas fa-check fa-sm" aria-hidden="true"></i>';
            link.classList.replace('btn-primary', 'btn-success');

            setTimeout(() => {
                link.innerHTML = original;
                link.classList.replace('btn-success', 'btn-primary');
                link.classList.remove('disabled');
            }, 1500);
        } catch (err) {
            console.error(`Erreur lors de l'ajout rapide au panier :`, err);
            link.innerHTML = '<i class="fas fa-times fa-sm" aria-hidden="true"></i>';
            link.classList.replace('btn-primary', 'btn-danger');
            setTimeout(() => {
                link.innerHTML = original;
                link.classList.replace('btn-danger', 'btn-primary');
                link.classList.remove('disabled');
            }, 1500);
        }
    }

    // --- Méthodes privées (helpers internes) ---

    // Affiche un message de retour (succès ou erreur) dans la zone de feedback
    _showFeedback(type, message) {
        if (!this.hasFeedbackTarget) return;
        const el = this.feedbackTarget;
        el.textContent = message;
        el.classList.remove('d-none', 'alert-success', 'alert-danger');
        el.classList.add(`alert-${type}`);
    }

    // Masque et réinitialise la zone de feedback
    _hideFeedback() {
        if (!this.hasFeedbackTarget) return;
        const el = this.feedbackTarget;
        el.textContent = '';
        el.classList.add('d-none');
        el.classList.remove('alert-success', 'alert-danger');
    }

    // Active ou désactive l'état de chargement sur un bouton
    // @param {HTMLElement} btn     - Le bouton concerné
    // @param {boolean}    loading - true = chargement en cours, false = état normal
    _setLoading(btn, loading) {
        if (loading) {
            btn.dataset.originalContent = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Ajout en cours...';
            btn.classList.add('disabled');
        } else {
            btn.innerHTML = btn.dataset.originalContent || '<i class="fas fa-cart-plus me-2"></i>Ajouter au panier';
            btn.classList.remove('disabled');
            delete btn.dataset.originalContent;
        }
    }

    // Met à jour le compteur (badge) du panier dans la barre de navigation
    // @param {number} count - Nombre total d'articles dans le panier
    _updateBadge(count) {
        const badge = document.querySelector('[data-cart-badge]');
        if (!badge || typeof count !== 'number') return;
        badge.textContent = count;
        if (count > 0) {
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }

    // Analyse du JSON de manière sécurisée : extrait le JSON même si la réponse contient du texte supplémentaire
    // @param {string} text - Le texte brut de la réponse serveur
    _safeJsonParse(text) {
        try {
            return JSON.parse(text);
        } catch (e) {
            // Tente d'extraire le JSON entre le premier '{' et le dernier '}'
            const start = text.indexOf('{');
            const end = text.lastIndexOf('}');
            if (start !== -1 && end !== -1 && end > start) {
                return JSON.parse(text.slice(start, end + 1));
            }
            throw e;
        }
    }

    // Incrémente le badge du panier de façon optimiste (sans données serveur)
    // Utilisé comme fallback si la réponse ne contient pas le compte total
    // @param {number} delta - Nombre d'articles à ajouter au compteur actuel
    _incrementBadgeFallback(delta) {
        const badge = document.querySelector('[data-cart-badge]');
        if (!badge) return;

        const current = parseInt(badge.textContent, 10) || 0;
        const next = Math.max(0, current + delta);
        this._updateBadge(next);
    }
}
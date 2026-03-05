import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = ["dialog", "title", "description", "price", "addToCartLink", "image", "placeholder", "feedback", "quantity"];

    open(event) {
        event.preventDefault();
        const data = event.currentTarget.dataset;

        this.titleTarget.textContent = data.name;
        this.descriptionTarget.textContent = data.description || "Aucune description disponible pour ce produit.";
        this.priceTarget.textContent = data.price + " €";
        this.addToCartLinkTarget.setAttribute('href', data.addToCartUrl);

        if (data.image) {
            this.imageTarget.src = data.image;
            this.imageTarget.alt = data.name;
            this.imageTarget.classList.remove('d-none');
            this.placeholderTarget.classList.add('d-none');
        } else {
            this.imageTarget.classList.add('d-none');
            this.placeholderTarget.classList.remove('d-none');
        }

        if (this.hasQuantityTarget) {
            this.quantityTarget.value = 1;
        }
        this._hideFeedback();

        Modal.getOrCreateInstance(this.dialogTarget).show();
    }

    stopPropagation(event) {
        event.stopPropagation();
    }

    increaseQuantity(event) {
        event.preventDefault();
        event.stopPropagation();
        if (!this.hasQuantityTarget) return;
        const current = parseInt(this.quantityTarget.value, 10) || 1;
        this.quantityTarget.value = Math.min(99, current + 1);
    }

    decreaseQuantity(event) {
        event.preventDefault();
        event.stopPropagation();
        if (!this.hasQuantityTarget) return;
        const current = parseInt(this.quantityTarget.value, 10) || 1;
        this.quantityTarget.value = Math.max(1, current - 1);
    }

    async addToCart(event) {
        event.preventDefault();
        event.stopPropagation();

        const url = this.addToCartLinkTarget.getAttribute('href');
        if (!url || url === '#') return;

        const btn = event.currentTarget;
        this._setLoading(btn, true);
        this._hideFeedback();

        let finalUrl = url;
        try {
            const urlObj = new URL(url, window.location.origin);
            if (this.hasQuantityTarget) {
                const qty = Math.max(1, Math.min(99, parseInt(this.quantityTarget.value, 10) || 1));
                urlObj.searchParams.set('quantity', qty);
            }
            finalUrl = urlObj.toString();
        } catch (_) {
            // on garde l'URL de base si la construction échoue
        }

        try {
            const response = await fetch(finalUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
            this._showFeedback('success', data.message || 'Produit ajouté au panier !');
            this._updateBadge(data.count);
        } catch (err) {
            console.error(`Erreur lors de l'ajout au panier :`, err);
            this._showFeedback('danger', 'Une erreur est survenue. Merci de réessayer.');
        } finally {
            this._setLoading(btn, false);
        }
    }

    async quickAdd(event) {
        event.preventDefault();
        event.stopPropagation();

        const link = event.currentTarget;
        const url = link.getAttribute('href');
        if (!url || url === '#') return;

        link.classList.add('disabled');
        const original = link.innerHTML;

        try {
            const response = await fetch(`${url}?quantity=1`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
            this._updateBadge(data.count);

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

    // --- Helpers privés ---

    _showFeedback(type, message) {
        if (!this.hasFeedbackTarget) return;
        const el = this.feedbackTarget;
        el.textContent = message;
        el.classList.remove('d-none', 'alert-success', 'alert-danger');
        el.classList.add(`alert-${type}`);
    }

    _hideFeedback() {
        if (!this.hasFeedbackTarget) return;
        const el = this.feedbackTarget;
        el.textContent = '';
        el.classList.add('d-none');
        el.classList.remove('alert-success', 'alert-danger');
    }

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
}
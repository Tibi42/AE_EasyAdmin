// Contrôleur Stimulus : gestion du consentement aux cookies (RGPD)
// Affiche un bandeau de consentement et permet à l'utilisateur de gérer ses préférences
import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

// Clé de stockage dans le localStorage
const STORAGE_KEY = 'cookie_consent';
// Identifiant du bandeau de consentement dans le DOM
const BAR_ID = 'cookie-consent-bar';
// Consentement par défaut : seuls les cookies essentiels sont acceptés
const DEFAULT_CONSENT = { essential: true, analytics: false, marketing: false };

export default class extends Controller {
    static values = {};
    // Cibles HTML : bandeau, boutons d'action, cases à cocher et modale de préférences
    static targets = ['bar', 'acceptAll', 'rejectNonEssential', 'savePreferences', 'analyticsCheckbox', 'marketingCheckbox', 'modal'];

    // Au montage : masque le bandeau si un consentement est déjà enregistré
    connect() {
        const consent = this.getConsent();
        if (consent !== null) {
            this.hideBar();
        }
        // Si pas de consentement : le bandeau reste visible
    }

    // Récupère le consentement stocké dans le localStorage
    // Retourne null si aucun consentement n'a encore été enregistré
    getConsent() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    }

    // Enregistre le consentement, masque le bandeau et émet un événement personnalisé
    setConsent(consent) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(consent));
        this.hideBar();
        document.getElementById(BAR_ID)?.classList.add('d-none');
        this.dispatch('cookie-consent-updated', { detail: { consent } });
    }

    // Affiche le bandeau de consentement
    showBar() {
        if (this.hasBarTarget) {
            this.barTarget.classList.remove('d-none');
        }
    }

    // Masque le bandeau de consentement (via la cible ou l'identifiant DOM)
    hideBar() {
        const el = this.hasBarTarget ? this.barTarget : document.getElementById(BAR_ID);
        if (el) el.classList.add('d-none');
    }

    // Accepte tous les cookies (essentiels + analytics + marketing)
    acceptAll(event) {
        if (event) event.preventDefault();
        this.setConsent({ essential: true, analytics: true, marketing: true });
    }

    // Refuse tous les cookies non essentiels
    rejectNonEssential(event) {
        if (event) event.preventDefault();
        this.setConsent({ ...DEFAULT_CONSENT });
    }

    // Ouvre la modale de gestion des préférences de cookies
    openPreferences(event) {
        if (event) event.preventDefault();
        this.syncModalCheckboxes();
        if (this.hasModalTarget) {
            const modalEl = this.modalTarget;
            const modal = Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    // Synchronise les cases à cocher de la modale avec le consentement actuel
    syncModalCheckboxes() {
        const consent = this.getConsent() || DEFAULT_CONSENT;
        if (this.hasAnalyticsCheckboxTarget) this.analyticsCheckboxTarget.checked = consent.analytics;
        if (this.hasMarketingCheckboxTarget) this.marketingCheckboxTarget.checked = consent.marketing;
    }

    // Sauvegarde les préférences choisies dans la modale puis ferme celle-ci
    savePreferences(event) {
        if (event) event.preventDefault();
        const essential = true; // Les cookies essentiels sont toujours activés
        const analytics = this.hasAnalyticsCheckboxTarget ? this.analyticsCheckboxTarget.checked : false;
        const marketing = this.hasMarketingCheckboxTarget ? this.marketingCheckboxTarget.checked : false;
        this.setConsent({ essential, analytics, marketing });
        if (this.hasModalTarget) {
            const modal = Modal.getOrCreateInstance(this.modalTarget);
            modal.hide();
        }
    }
}

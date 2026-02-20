import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

const STORAGE_KEY = 'cookie_consent';
const BAR_ID = 'cookie-consent-bar';
const DEFAULT_CONSENT = { essential: true, analytics: false, marketing: false };

export default class extends Controller {
    static values = {};
    static targets = ['bar', 'acceptAll', 'rejectNonEssential', 'savePreferences', 'analyticsCheckbox', 'marketingCheckbox', 'modal'];

    connect() {
        const consent = this.getConsent();
        if (consent !== null) {
            this.hideBar();
        }
        // Si pas de consentement : le bandeau reste visible (pas de d-none dans le HTML)
    }

    getConsent() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    }

    setConsent(consent) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(consent));
        this.hideBar();
        document.getElementById(BAR_ID)?.classList.add('d-none');
        this.dispatch('cookie-consent-updated', { detail: { consent } });
    }

    showBar() {
        if (this.hasBarTarget) {
            this.barTarget.classList.remove('d-none');
        }
    }

    hideBar() {
        const el = this.hasBarTarget ? this.barTarget : document.getElementById(BAR_ID);
        if (el) el.classList.add('d-none');
    }

    acceptAll(event) {
        if (event) event.preventDefault();
        this.setConsent({ essential: true, analytics: true, marketing: true });
    }

    rejectNonEssential(event) {
        if (event) event.preventDefault();
        this.setConsent({ ...DEFAULT_CONSENT });
    }

    openPreferences(event) {
        if (event) event.preventDefault();
        this.syncModalCheckboxes();
        if (this.hasModalTarget) {
            const modalEl = this.modalTarget;
            const modal = Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    syncModalCheckboxes() {
        const consent = this.getConsent() || DEFAULT_CONSENT;
        if (this.hasAnalyticsCheckboxTarget) this.analyticsCheckboxTarget.checked = consent.analytics;
        if (this.hasMarketingCheckboxTarget) this.marketingCheckboxTarget.checked = consent.marketing;
    }

    savePreferences(event) {
        if (event) event.preventDefault();
        const essential = true;
        const analytics = this.hasAnalyticsCheckboxTarget ? this.analyticsCheckboxTarget.checked : false;
        const marketing = this.hasMarketingCheckboxTarget ? this.marketingCheckboxTarget.checked : false;
        this.setConsent({ essential, analytics, marketing });
        if (this.hasModalTarget) {
            const modal = Modal.getOrCreateInstance(this.modalTarget);
            modal.hide();
        }
    }
}

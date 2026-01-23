import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    // Affiche la modale si l'âge n'a pas encore été vérifié
    connect() {
        if (!this.getCookie('age_verified')) {
            const modal = new Modal(this.element);
            modal.show();
        }
    }

    // Enregistre la vérification dans un cookie (valable 30 jours)
    verify() {
        this.setCookie('age_verified', 'true', 30);
    }

    // Fonctions utilitaires pour la gestion des cookies
    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${date.toUTCString()};path=/;SameSite=Lax`;
    }
}

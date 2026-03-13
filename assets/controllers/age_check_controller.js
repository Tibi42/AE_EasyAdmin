// Contrôleur Stimulus : vérification de l'âge
// Affiche une modale de confirmation d'âge si l'utilisateur ne l'a pas encore validée
import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    // Au montage du contrôleur, vérifie si l'âge a déjà été confirmé
    connect() {
        if (!this.getCookie('age_verified')) {
            // Aucun cookie de vérification : on affiche la modale
            const modal = new Modal(this.element);
            modal.show();
        }
    }

    // Appelé quand l'utilisateur confirme son âge
    // Enregistre la vérification dans un cookie valable 30 jours
    verify() {
        this.setCookie('age_verified', 'true', 30);
    }

    // Récupère la valeur d'un cookie par son nom
    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    // Crée un cookie avec un nom, une valeur et une durée en jours
    setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${date.toUTCString()};path=/;SameSite=Lax`;
    }
}

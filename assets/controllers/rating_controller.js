// Contrôleur Stimulus : système de notation par étoiles
// Permet à l'utilisateur de sélectionner une note de 1 à 5 étoiles
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    // Cibles : les éléments étoile et le champ caché pour stocker la valeur
    static targets = ['star', 'input'];
    // Valeur courante de la note (0 par défaut = aucune note)
    static values = {
        rating: { type: Number, default: 0 }
    };

    // Au montage : initialise l'affichage depuis la valeur du champ (si existante)
    connect() {
        if (this.hasInputTarget && this.inputTarget.value) {
            this.ratingValue = parseInt(this.inputTarget.value);
        }
        this.updateStars(this.ratingValue);
    }

    // Sélectionne une note définitivement au clic sur une étoile
    select(event) {
        const rating = parseInt(event.currentTarget.dataset.value);
        this.ratingValue = rating;
        if (this.hasInputTarget) {
            this.inputTarget.value = rating; // Met à jour le champ de formulaire
        }
        this.updateStars(rating);
    }

    // Affiche un aperçu de la note au survol d'une étoile
    hover(event) {
        const rating = parseInt(event.currentTarget.dataset.value);
        this.updateStars(rating);
    }

    // Restaure l'affichage sur la note sélectionnée quand la souris quitte les étoiles
    reset() {
        this.updateStars(this.ratingValue);
    }

    // Met à jour la couleur des étoiles selon la note donnée
    // @param {number} rating - Nombre d'étoiles à colorer (de 1 à 5)
    updateStars(rating) {
        this.starTargets.forEach((star, index) => {
            if (index < rating) {
                star.style.color = '#ffd700'; // Or : étoile active
            } else {
                star.style.color = '#ddd'; // Gris clair : étoile inactive
            }
        });
    }
}

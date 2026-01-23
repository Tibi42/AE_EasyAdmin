import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['star', 'input'];
    static values = {
        rating: { type: Number, default: 0 }
    };

    connect() {
        if (this.hasInputTarget && this.inputTarget.value) {
            this.ratingValue = parseInt(this.inputTarget.value);
        }
        this.updateStars(this.ratingValue);
    }

    select(event) {
        const rating = parseInt(event.currentTarget.dataset.value);
        this.ratingValue = rating;
        if (this.hasInputTarget) {
            this.inputTarget.value = rating;
        }
        this.updateStars(rating);
    }

    hover(event) {
        const rating = parseInt(event.currentTarget.dataset.value);
        this.updateStars(rating);
    }

    reset() {
        this.updateStars(this.ratingValue);
    }

    updateStars(rating) {
        this.starTargets.forEach((star, index) => {
            if (index < rating) {
                star.style.color = '#ffd700'; // Gold
            } else {
                star.style.color = '#ddd'; // Light Gray
            }
        });
    }
}

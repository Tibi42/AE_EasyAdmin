import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = ["dialog", "title", "description", "price", "addToCartLink", "image", "placeholder"];

    open(event) {
        event.preventDefault();
        const button = event.currentTarget;
        const data = button.dataset;

        // Populate content
        this.titleTarget.textContent = data.name;
        this.descriptionTarget.textContent = data.description || "Aucune description disponible pour ce produit.";
        this.priceTarget.textContent = data.price + " €";
        this.addToCartLinkTarget.setAttribute('href', data.addToCartUrl);

        // Handle Image
        if (data.image) {
            this.imageTarget.src = data.image;
            this.imageTarget.alt = data.name;
            this.imageTarget.classList.remove('d-none');
            this.placeholderTarget.classList.add('d-none');
        } else {
            this.imageTarget.classList.add('d-none');
            this.placeholderTarget.classList.remove('d-none');
        }

        // Show Modal
        const modal = Modal.getOrCreateInstance(this.dialogTarget);
        modal.show();
    }

    stopPropagation(event) {
        event.stopPropagation();
    }
}

// Contrôleur Stimulus : recherche en temps réel (live search)
// Interroge l'API de recherche au fil de la frappe et affiche les résultats
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    // Cibles : champ de saisie et zone d'affichage des résultats
    static targets = ['input', 'results'];
    // Valeur configurée via data-live-search-url-value dans le HTML
    static values = {
        url: String
    };

    // Initialise le minuteur de debounce
    connect() {
        this.timeout = null;
    }

    // Déclenche la recherche avec un délai (debounce) pour limiter les appels réseau
    onSearch() {
        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => {
            this.performSearch();
        }, 300); // Attend 300 ms après la dernière frappe
    }

    // Effectue la requête AJAX vers l'API de recherche
    async performSearch() {
        const query = this.inputTarget.value.trim();

        // N'effectue la recherche qu'à partir de 2 caractères
        if (query.length < 2) {
            this.resultsTarget.innerHTML = '';
            this.resultsTarget.classList.remove('active');
            return;
        }

        try {
            const response = await fetch(`${this.urlValue}?q=${encodeURIComponent(query)}`);
            const data = await response.json();

            this.renderResults(data);
        } catch (error) {
            console.error('Erreur de recherche :', error);
        }
    }

    // Affiche les résultats de recherche dans la zone dédiée
    // @param {Array} products - Liste des produits retournés par l'API
    renderResults(products) {
        if (products.length === 0) {
            this.resultsTarget.innerHTML = '<div class="search-result-item no-result">Aucun produit trouvé</div>';
        } else {
            const html = products.map(product => `
                <a href="/product?q=${encodeURIComponent(product.name)}" class="search-result-item" style="text-decoration: none;">
                    ${product.image ? `<img src="${product.image}" alt="${product.name}" class="search-result-image">` : '<div class="search-result-placeholder">📦</div>'}
                    <div class="search-result-info">
                        <div class="search-result-name">${product.name}</div>
                        <div class="search-result-price">${product.price}</div>
                    </div>
                </a>
            `).join('');
            this.resultsTarget.innerHTML = html;
        }
        this.resultsTarget.classList.add('active');
    }

    // Masque les résultats lorsque le champ perd le focus
    // Le délai de 200 ms permet de cliquer sur un résultat avant qu'il disparaisse
    hideResults(event) {
        setTimeout(() => {
            this.resultsTarget.classList.remove('active');
        }, 200);
    }
}

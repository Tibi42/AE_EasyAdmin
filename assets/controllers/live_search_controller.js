import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'results'];
    static values = {
        url: String
    };

    connect() {
        this.timeout = null;
    }

    onSearch() {
        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => {
            this.performSearch();
        }, 300);
    }

    async performSearch() {
        const query = this.inputTarget.value.trim();

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
            console.error('Search error:', error);
        }
    }

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

    hideResults(event) {
        // Delay hiding to allow clicking on results
        setTimeout(() => {
            this.resultsTarget.classList.remove('active');
        }, 200);
    }
}

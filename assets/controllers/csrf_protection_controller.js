// Contrôleur de protection CSRF (Cross-Site Request Forgery)
// Implémente la stratégie "double-submit cookie" de Symfony (SameOriginCsrfTokenManager)

// Expression régulière validant le nom du cookie CSRF (4 à 22 caractères alphanumériques)
const nameCheck = /^[-_a-zA-Z0-9]{4,22}$/;
// Expression régulière validant le token CSRF (au moins 24 caractères base64)
const tokenCheck = /^[-_/+a-zA-Z0-9]{24,}$/;

// Génère et soumet le token CSRF à chaque soumission de formulaire
// Utiliser form.requestSubmit() (et non form.submit()) pour que cet écouteur soit déclenché
document.addEventListener('submit', function (event) {
    generateCsrfToken(event.target);
}, true);

// Lorsque Turbo gère une soumission de formulaire, envoie le token CSRF dans un en-tête HTTP
// Nécessite l'option `framework.csrf_protection.check_header` activée dans la config Symfony
document.addEventListener('turbo:submit-start', function (event) {
    const h = generateCsrfHeaders(event.detail.formSubmission.formElement);
    Object.keys(h).map(function (k) {
        event.detail.formSubmission.fetchRequest.headers[k] = h[k];
    });
});

// Supprime le cookie CSRF une fois le formulaire soumis via Turbo
document.addEventListener('turbo:submit-end', function (event) {
    removeCsrfToken(event.detail.formSubmission.formElement);
});

// Génère un token CSRF et le stocke en double-submit (champ caché + cookie)
// @param {HTMLFormElement} formElement - Le formulaire à protéger
export function generateCsrfToken (formElement) {
    const csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]');

    if (!csrfField) {
        return;
    }

    let csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');
    let csrfToken = csrfField.value;

    if (!csrfCookie && nameCheck.test(csrfToken)) {
        // Génère un token aléatoire sécurisé et le stocke dans le champ
        csrfField.setAttribute('data-csrf-protection-cookie-value', csrfCookie = csrfToken);
        csrfField.defaultValue = csrfToken = btoa(String.fromCharCode.apply(null, (window.crypto || window.msCrypto).getRandomValues(new Uint8Array(18))));
    }
    csrfField.dispatchEvent(new Event('change', { bubbles: true }));

    if (csrfCookie && tokenCheck.test(csrfToken)) {
        // Définit le cookie CSRF (préfixe __Host- en HTTPS pour plus de sécurité)
        const cookie = csrfCookie + '_' + csrfToken + '=' + csrfCookie + '; path=/; samesite=strict';
        document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;
    }
}

// Retourne les en-têtes HTTP contenant le token CSRF pour les requêtes Turbo
// @param {HTMLFormElement} formElement - Le formulaire soumis
// @returns {Object} En-têtes à ajouter à la requête
export function generateCsrfHeaders (formElement) {
    const headers = {};
    const csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]');

    if (!csrfField) {
        return headers;
    }

    const csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');

    if (tokenCheck.test(csrfField.value) && nameCheck.test(csrfCookie)) {
        headers[csrfCookie] = csrfField.value;
    }

    return headers;
}

// Supprime le cookie CSRF en le faisant expirer immédiatement
// @param {HTMLFormElement} formElement - Le formulaire dont le token doit être supprimé
export function removeCsrfToken (formElement) {
    const csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]');

    if (!csrfField) {
        return;
    }

    const csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');

    if (tokenCheck.test(csrfField.value) && nameCheck.test(csrfCookie)) {
        // Expire le cookie immédiatement via max-age=0
        const cookie = csrfCookie + '_' + csrfField.value + '=0; path=/; samesite=strict; max-age=0';

        document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;
    }
}

/* stimulusFetch: 'eager' */
export default 'csrf-protection-controller';

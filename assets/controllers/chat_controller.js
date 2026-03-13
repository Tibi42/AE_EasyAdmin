// Contrôleur Stimulus : fenêtre de chat
// Gère l'ouverture, la fermeture et l'envoi de messages dans le widget de chat
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    // Cibles HTML : la fenêtre de chat, la zone de messages et le champ de saisie
    static targets = ['window', 'messages', 'input'];

    // Ouvre la fenêtre de chat et affiche le message de bienvenue si vide
    open() {
        this.windowTarget.classList.add('active');
        if (this.messagesTarget.children.length === 0) {
            this.addMessage('Assistant', 'Bonjour ! Comment puis-je vous aider aujourd\'hui ?', 'received');
        }
    }

    // Ferme la fenêtre de chat
    close() {
        this.windowTarget.classList.remove('active');
    }

    // Envoie le message saisi par l'utilisateur et simule une réponse automatique
    send(event) {
        event.preventDefault();
        const text = this.inputTarget.value.trim();
        if (text) {
            // Affiche le message de l'utilisateur
            this.addMessage('Vous', text, 'sent');
            this.inputTarget.value = '';

            // Simulation d'une réponse de l'assistant après 1 seconde
            setTimeout(() => {
                this.addMessage('Assistant', 'Merci pour votre message. Un conseiller va vous répondre sous peu.', 'received');
            }, 1000);
        }
    }

    // Crée et ajoute un message dans la zone de discussion
    // @param {string} author - Nom de l'expéditeur
    // @param {string} text   - Contenu du message
    // @param {string} type   - 'sent' (envoyé) ou 'received' (reçu)
    addMessage(author, text, type) {
        const div = document.createElement('div');
        div.className = `chat-message ${type}`;
        div.innerHTML = `
            <div class="message-author">${author}</div>
            <div class="message-text">${text}</div>
        `;
        this.messagesTarget.appendChild(div);
        // Défile automatiquement vers le bas pour afficher le dernier message
        this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
    }
}

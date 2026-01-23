import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['window', 'messages', 'input'];

    open() {
        this.windowTarget.classList.add('active');
        if (this.messagesTarget.children.length === 0) {
            this.addMessage('Assistant', 'Bonjour ! Comment puis-je vous aider aujourd\'hui ?', 'received');
        }
    }

    close() {
        this.windowTarget.classList.remove('active');
    }

    send(event) {
        event.preventDefault();
        const text = this.inputTarget.value.trim();
        if (text) {
            this.addMessage('Vous', text, 'sent');
            this.inputTarget.value = '';

            // Simulation d'une réponse
            setTimeout(() => {
                this.addMessage('Assistant', 'Merci pour votre message. Un conseiller va vous répondre sous peu.', 'received');
            }, 1000);
        }
    }

    addMessage(author, text, type) {
        const div = document.createElement('div');
        div.className = `chat-message ${type}`;
        div.innerHTML = `
            <div class="message-author">${author}</div>
            <div class="message-text">${text}</div>
        `;
        this.messagesTarget.appendChild(div);
        this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
    }
}

// Point d'entrée principal de l'application JavaScript
// Ce fichier est inclus dans les pages via la fonction Twig importmap()

// Initialise les contrôleurs Stimulus
import './stimulus_bootstrap.js';

// Importe les styles et le JS de Bootstrap
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';

// Importe les styles globaux de l'application
import './styles/app.css';

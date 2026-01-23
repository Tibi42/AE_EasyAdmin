<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber pour ajouter des entêtes de sécurité à toutes les réponses HTTP
 */
class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    private string $environment;

    public function __construct(string $environment = 'dev')
    {
        $this->environment = $environment;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        // Protection contre le clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Protection contre le reniflage de type MIME (MIME-sniffing)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Activation du filtre XSS du navigateur (pour les anciens navigateurs)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Politique de référent (Referrer Policy)
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (désactivation de fonctionnalités inutilisées)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Strict-Transport-Security (HSTS) - Activé en production avec HTTPS
        if ($this->environment === 'prod') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy (CSP) de base
        // Note: 'unsafe-inline' et 'unsafe-eval' sont nécessaires pour certains frameworks JS
        // En production, envisager d'utiliser des nonces pour renforcer la sécurité
        $csp = "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' data: https://cdn.jsdelivr.net https://kit.fontawesome.com https://cdnjs.cloudflare.com https://ga.jspm.io https://videos.pexels.com; " .
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://ka-f.fontawesome.com https://cdnjs.cloudflare.com; " .
            "img-src 'self' data: https://images.unsplash.com https://picsum.photos https://vectorlogo.zone https://www.vectorlogo.zone https://i.pravatar.cc https://videos.pexels.com; " .
            "media-src 'self' https://videos.pexels.com; " .
            "font-src 'self' data: https://fonts.gstatic.com https://ka-f.fontawesome.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
            "connect-src 'self' https://ka-f.fontawesome.com https://vectorlogo.zone https://www.vectorlogo.zone https://videos.pexels.com; " .
            "frame-ancestors 'none'; " .
            "base-uri 'self'; " .
            "form-action 'self' https://checkout.stripe.com; " .
            "worker-src 'self' blob:;";

        $response->headers->set('Content-Security-Policy', $csp);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}

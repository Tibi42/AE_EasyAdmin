<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Abonné d'événements gérant la synchronisation du panier lors de la connexion
 * 
 * Fusionne le panier de session (panier anonyme) avec le panier sauvegardé de l'utilisateur
 * lors de la connexion, puis synchronise les deux pour une expérience utilisateur fluide.
 */
class LoginCartSubscriber implements EventSubscriberInterface
{
    private $requestStack;
    private $entityManager;

    /**
     * Constructeur du subscriber
     * 
     * @param RequestStack $requestStack Pile de requêtes pour accéder à la session
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités pour sauvegarder le panier
     */
    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    /**
     * Gère la synchronisation du panier lors de la connexion
     * 
     * Fusionne le panier de session (panier temporaire) avec le panier sauvegardé
     * de l'utilisateur. Si un produit existe dans les deux paniers, les quantités
     * sont additionnées. Le panier fusionné est ensuite sauvegardé en session et
     * dans la base de données.
     * 
     * @param InteractiveLoginEvent $event L'événement de connexion interactive
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        // Vérification que l'utilisateur est une instance de User
        if (!$user instanceof User) {
            return;
        }

        // Récupération des paniers (session et utilisateur)
        $session = $this->requestStack->getSession();
        $sessionCart = $session->get('cart', []);
        $userCart = $user->getCart() ?? [];

        // Fusion des paniers : addition des quantités si le produit existe déjà
        foreach ($userCart as $id => $quantity) {
            if (isset($sessionCart[$id])) {
                // Addition des quantités si le produit existe dans les deux paniers
                $sessionCart[$id] += $quantity;
            } else {
                // Ajout du produit s'il n'existe que dans le panier utilisateur
                $sessionCart[$id] = $quantity;
            }
        }

        // Mise à jour de la session avec le panier fusionné
        $session->set('cart', $sessionCart);

        // Sauvegarde du panier fusionné dans la base de données
        $user->setCart($sessionCart);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Retourne les événements auxquels ce subscriber est abonné
     * 
     * @return array Les événements et leurs méthodes associées
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
        ];
    }
}

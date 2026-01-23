<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Vérifie l'état du compte utilisateur lors de l'authentification
 * 
 * Ce UserChecker est utilisé par le système de sécurité de Symfony pour valider
 * l'état des comptes utilisateurs avant et après l'authentification. Il empêche
 * notamment la connexion des comptes désactivés.
 * 
 * @see UserCheckerInterface Interface Symfony pour les vérifications d'utilisateurs
 */
class UserChecker implements UserCheckerInterface
{
    /**
     * Vérifie l'état de l'utilisateur avant l'authentification
     * 
     * Cette méthode est appelée avant que les credentials ne soient vérifiés.
     * Elle vérifie si le compte est actif et bloque la connexion si le compte
     * a été désactivé par un administrateur.
     * 
     * @param UserInterface $user L'utilisateur à vérifier
     * @throws CustomUserMessageAccountStatusException Si le compte est désactivé
     */
    public function checkPreAuth(UserInterface $user): void
    {
        // Vérifie que l'utilisateur est une instance de notre entité User
        if (!$user instanceof User) {
            return;
        }

        // Vérifie si le compte est actif
        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
            );
        }
    }

    /**
     * Vérifie l'état de l'utilisateur après l'authentification
     * 
     * Cette méthode est appelée après que les credentials aient été vérifiés
     * avec succès. Dans notre cas, aucune vérification supplémentaire n'est
     * nécessaire car tout est vérifié dans checkPreAuth.
     * 
     * @param UserInterface $user L'utilisateur à vérifier
     * @param TokenInterface|null $token Le token d'authentification (optionnel)
     */
    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // Pas de vérification supplémentaire après l'authentification
        // Toutes les vérifications sont effectuées dans checkPreAuth
    }
}

<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils; // Added for SecurityController

/**
 * Contrôleur gérant l'inscription des nouveaux utilisateurs
 * 
 * Permet la création d'un nouveau compte client avec hachage sécurisé du mot de passe
 * et connexion automatique suite à la validation du formulaire.
 */
class RegistrationController extends AbstractController
{
    /**
     * Gère l'affichage du formulaire et le processus d'inscription
     * 
     * @param Request $request La requête HTTP entrante
     * @param UserPasswordHasherInterface $userPasswordHasher Le service de hachage de mot de passe
     * @param Security $security Le service de sécurité pour la connexion automatique
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités de Doctrine
     * @return Response Une instance de Response vers la vue d'inscription ou une redirection après succès
     */
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Hachage du mot de passe en clair
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Persistance de l'utilisateur en base de données
            $entityManager->persist($user);
            $entityManager->flush();

            // Connexion automatique de l'utilisateur après inscription
            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}

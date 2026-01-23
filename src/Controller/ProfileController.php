<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur gérant le compte utilisateur côté client
 */
class ProfileController extends AbstractController
{
    /**
     * Affiche et gère le profil utilisateur et le changement de mot de passe
     */
    #[Route('/profile', name: 'app_profile')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Redirige si non connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // --- Gestion du Formulaire de Profil ---
        $profileForm = $this->createForm(ProfileFormType::class, $user);
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        // --- Gestion du Formulaire de Changement de Mot de Passe ---
        $passwordForm = $this->createForm(ChangePasswordFormType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $currentPassword = $passwordForm->get('currentPassword')->getData();
            $newPassword = $passwordForm->get('newPassword')->getData();

            // Vérification du mot de passe actuel avant modification
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            } else {
                // Hachage et mise à jour du nouveau mot de passe
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $entityManager->flush();

                $this->addFlash('success', 'Votre mot de passe a été modifié avec succès !');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('profile/index.html.twig', [
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
            'user' => $user,
        ]);
    }

    /**
     * Supprime le compte de l'utilisateur
     */
    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete_account', $request->request->get('_token'))) {
            $password = $request->request->get('password');

            if (!$passwordHasher->isPasswordValid($user, $password)) {
                $this->addFlash('error', 'Le mot de passe est incorrect. Impossible de supprimer le compte.');
                return $this->redirectToRoute('app_profile');
            }

            // Déconnexion de l'utilisateur
            $tokenStorage->setToken(null);
            $request->getSession()->invalidate();

            // Suppression des commandes associées pour éviter les erreurs de clé étrangère
            $orders = $entityManager->getRepository(\App\Entity\Order::class)->findBy(['user' => $user]);
            foreach ($orders as $order) {
                $entityManager->remove($order);
            }

            // Suppression de l'utilisateur
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte a été supprimé avec succès. Au revoir !');
            return $this->redirectToRoute('app_home');
        }

        $this->addFlash('error', 'Une erreur est survenue lors de la suppression du compte.');
        return $this->redirectToRoute('app_profile');
    }
}

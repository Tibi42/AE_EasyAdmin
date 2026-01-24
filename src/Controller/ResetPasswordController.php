<?php

namespace App\Controller;

use App\Form\ResetPasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as MimeEmail;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur gérant la réinitialisation du mot de passe
 */
class ResetPasswordController extends AbstractController
{
    /**
     * Gère la demande de réinitialisation de mot de passe (envoi d'email)
     */
    #[Route('/reset-password', name: 'app_forgot_password_request')]
    public function request(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            // Toujours afficher le même message pour ne pas révéler si l'email existe
            $this->addFlash('success', 'Si un compte existe avec cet email, un lien de réinitialisation vous a été envoyé.');

            if ($user) {
                // Générer un token de réinitialisation
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour')); // Token valide 1 heure
                $entityManager->flush();

                $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], true);
                $from = getenv('MAILER_FROM') ?: 'noreply@auxilia-ecommerce.com';

                try {
                    $email = (new MimeEmail())
                        ->from($from)
                        ->to($user->getEmail())
                        ->subject('Réinitialisation de votre mot de passe - Auxilia E-commerce')
                        ->html($this->renderView('emails/reset_password.html.twig', [
                            'resetUrl' => $resetUrl,
                            'firstName' => $user->getFirstName(),
                        ]));
                    $mailer->send($email);
                } catch (\Throwable $e) {
                    // En dev : afficher le lien en flash si l'envoi échoue (ex. MAILER_DSN=null://)
                    if ($this->getParameter('kernel.environment') === 'dev') {
                        $this->addFlash('info', 'Lien de réinitialisation (envoi mail en échec) : ' . $resetUrl);
                    }
                }
            }

            return $this->redirectToRoute('app_forgot_password_request');
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    /**
     * Gère la réinitialisation effective du mot de passe via le token
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            // Hasher et sauvegarder le nouveau mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès ! Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }
}

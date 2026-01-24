<?php

namespace App\Controller;

use App\Entity\Newsletter;
use App\Repository\NewsletterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class NewsletterController extends AbstractController
{
    /**
     * Inscription à la newsletter (API endpoint)
     */
    #[Route('/newsletter/subscribe', name: 'app_newsletter_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        EntityManagerInterface $entityManager,
        NewsletterRepository $newsletterRepository,
        ValidatorInterface $validator,
        \Symfony\Component\RateLimiter\RateLimiterFactory $newsletterLimiter
    ): JsonResponse {
        $limiter = $newsletterLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Trop de tentatives. Veuillez réessayer plus tard.'
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Vérification CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('newsletter_subscribe', $submittedToken)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Jeton CSRF invalide.'
            ], Response::HTTP_FORBIDDEN);
        }

        $email = $request->request->get('email');

        if (!$email) {
            return new JsonResponse([
                'success' => false,
                'message' => 'L\'adresse email est obligatoire.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'email est déjà inscrit
        if ($newsletterRepository->isEmailSubscribed($email)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cette adresse email est déjà inscrite à la newsletter.'
            ], Response::HTTP_CONFLICT);
        }

        // Créer le nouvel abonné
        $newsletter = new Newsletter();
        $newsletter->setEmail($email);

        // Valider l'entité
        $errors = $validator->validate($newsletter);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse([
                'success' => false,
                'message' => implode(' ', $errorMessages)
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($newsletter);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Merci ! Vous êtes maintenant inscrit à notre newsletter.'
        ]);
    }
}

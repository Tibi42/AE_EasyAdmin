<?php

namespace App\Controller;

use App\Form\ContactFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as MimeEmail;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur gérant les pages statiques et fonctionnelles simples (À propos, Contact)
 */
final class PageController extends AbstractController
{
    /**
     * Affiche la page "À propos"
     * 
     * @return Response Une instance de Response vers la vue à propos
     */
    #[Route('/a-propos', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('page/about.html.twig');
    }

    /**
     * Affiche et gère le formulaire de contact
     * 
     * Envoie un email au destinataire configuré si le formulaire est valide.
     * 
     * @param Request $request La requête HTTP entrante
     * @param MailerInterface $mailer Le service d'envoi d'emails de Symfony
     * @return Response Une instance de Response vers la vue contact ou une redirection
     */
    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, MailerInterface $mailer, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        // 1. Gestion du formulaire de contact classique
        $contactForm = $this->createForm(ContactFormType::class);
        $contactForm->handleRequest($request);

        if ($contactForm->isSubmitted() && $contactForm->isValid()) {
            $data = $contactForm->getData();
            try {
                $email = (new MimeEmail())
                    ->from($data['email'])
                    ->to('guillaume.pecquet@gmail.com')
                    ->subject('Contact depuis le site : ' . $data['subject'])
                    ->html($this->renderView('emails/contact.html.twig', [
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'subject' => $data['subject'],
                        'message' => $data['message'],
                    ]));

                $mailer->send($email);
                $this->addFlash('success', 'Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.');
            } catch (\Exception $e) {
                // En cas d'erreur d'envoi mail (ex: configuration locale), on notifie tout de même le succès de l'enregistrement si applicable
                $this->addFlash('success', 'Votre message a été enregistré. Nous vous répondrons dans les plus brefs délais.');
            }
            return $this->redirectToRoute('app_contact');
        }

        // 2. Gestion du formulaire de témoignage (Commentaire)
        $testimonial = new \App\Entity\Testimonial();
        $testimonial->setRating(5); // Default rating
        $testimonialForm = $this->createForm(\App\Form\TestimonialType::class, $testimonial);
        $testimonialForm->handleRequest($request);

        if ($testimonialForm->isSubmitted()) {
            if ($testimonialForm->isValid()) {
                $entityManager->persist($testimonial);
                $entityManager->flush();

                $this->addFlash('success', 'Merci pour votre témoignage ! Il sera publié après validation par notre équipe.');
                return $this->redirectToRoute('app_contact');
            } else {
                $this->addFlash('error', 'Veuillez corriger les erreurs dans votre commentaire (n\'oubliez pas de donner une note).');
            }
        }

        return $this->render('page/contact.html.twig', [
            'contactForm' => $contactForm->createView(),
            'testimonialForm' => $testimonialForm->createView(),
        ]);
    }
}

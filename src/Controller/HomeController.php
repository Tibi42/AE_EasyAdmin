<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur gérant la page d'accueil du site
 */
final class HomeController extends AbstractController
{
    /**
     * Affiche la page d'accueil avec les derniers produits ajoutés
     */
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository, \App\Repository\TestimonialRepository $testimonialRepository): Response
    {
        // Récupère les produits mis en avant pour la section "Produits à la une" (avec cache)
        $featuredProducts = $productRepository->findFeaturedProducts(8);

        // Récupère les témoignages approuvés depuis la base de données
        $dbTestimonials = $testimonialRepository->findApproved(3);

        $testimonials = [];
        foreach ($dbTestimonials as $t) {
            $testimonials[] = [
                'author' => $t->getName(),
                'rating' => $t->getRating(),
                'content' => $t->getContent(),
                'avatar' => 'https://i.pravatar.cc/150?u=' . md5($t->getEmail()), // Avatar dynamique basé sur l'email
                'date' => 'Le ' . $t->getCreatedAt()->format('d/m/Y')
            ];
        }

        // Si aucun témoignage en base, on affiche des témoignages sur la coopérative de vin
        if (empty($testimonials)) {
            $testimonials = [
                [
                    'author' => 'Marie-Claire Fontaine',
                    'rating' => 5,
                    'content' => 'Un Côtes-du-Rhône d\'une richesse exceptionnelle ! On sent vraiment le travail passionné des vignerons. Je commande régulièrement pour mes repas de famille.',
                    'avatar' => 'https://i.pravatar.cc/150?u=marieclaire',
                    'date' => 'Cliente fidèle depuis 2019'
                ],
                [
                    'author' => 'Jean-Philippe Moreau',
                    'rating' => 5,
                    'content' => 'Restaurateur depuis 20 ans, j\'ai enfin trouvé ma cave de confiance. Les conseils du sommelier sont précieux et les vins subliment mes plats. Une coopérative qui mérite d\'être connue !',
                    'avatar' => 'https://i.pravatar.cc/150?u=jeanphilippe',
                    'date' => 'Chef restaurateur à Lyon'
                ],
                [
                    'author' => 'Isabelle Dupont',
                    'rating' => 5,
                    'content' => 'La visite du domaine était magique. Découvrir le savoir-faire de ces vignerons passionnés et déguster directement dans les caves... Une expérience inoubliable que je recommande à tous les amateurs de vin.',
                    'avatar' => 'https://i.pravatar.cc/150?u=isabelle',
                    'date' => 'Œnophile amateur'
                ],
                [
                    'author' => 'François Leroy',
                    'rating' => 4,
                    'content' => 'Excellent rapport qualité-prix sur toute la gamme. Le rosé d\'été est devenu incontournable pour nos apéritifs en terrasse. Livraison rapide et soignée, les bouteilles arrivent en parfait état.',
                    'avatar' => 'https://i.pravatar.cc/150?u=francois',
                    'date' => 'Client depuis 2021'
                ],
                [
                    'author' => 'Catherine Berger',
                    'rating' => 5,
                    'content' => 'J\'ai offert un coffret découverte pour Noël et toute la famille était ravie. Le Châteauneuf-du-Pape a fait l\'unanimité ! Bravo pour cette sélection qui met en valeur le terroir.',
                    'avatar' => 'https://i.pravatar.cc/150?u=catherine',
                    'date' => 'Cliente satisfaite'
                ]
            ];
        }

        return $this->render('home/index.html.twig', [
            'featuredProducts' => $featuredProducts,
            'testimonials' => $testimonials,
        ]);
    }
}

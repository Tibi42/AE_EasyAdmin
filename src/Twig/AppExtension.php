<?php

namespace App\Twig;

use App\Service\CartService;
use App\Repository\CategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension Twig personnalisée pour exposer des fonctionnalités globales aux templates
 */
class AppExtension extends AbstractExtension
{
    private $cartService;
    private $categoryRepository;

    public function __construct(CartService $cartService, CategoryRepository $categoryRepository)
    {
        $this->cartService = $cartService;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Définit les fonctions personnalisées utilisables dans Twig
     */
    public function getFunctions(): array
    {
        return [
            // Permet d'utiliser {{ cart_count() }} dans n'importe quel template
            new TwigFunction('cart_count', [$this, 'getCartCount']),
            // Permet d'utiliser {{ get_categories() }} pour lister les catégories
            new TwigFunction('get_categories', [$this, 'getCategories']),
        ];
    }

    /**
     * Récupère le nombre total d'articles du panier via le CartService
     */
    public function getCartCount(): int
    {
        return $this->cartService->getQuantitySum();
    }

    /**
     * Récupère toutes les catégories triées par nom
     */
    public function getCategories(): array
    {
        return $this->categoryRepository->findAllOrderedByName();
    }
}

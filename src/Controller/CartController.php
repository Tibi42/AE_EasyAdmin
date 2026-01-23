<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\CartService;

/**
 * Contrôleur gérant les interactions avec le panier d'achat
 */
final class CartController extends AbstractController
{
    /**
     * Affiche le contenu du panier
     */
    #[Route('/cart', name: 'cart_index')]
    public function index(CartService $cartService): Response
    {
        return $this->render('cart/index.html.twig', [
            'cart' => $cartService->getFullCart(),
            'total' => $cartService->getTotal(),
        ]);
    }

    /**
     * Ajoute un produit au panier
     */
    #[Route('/cart/add/{id}', name: 'cart_add')]
    public function add(int $id, CartService $cartService, Request $request): Response
    {
        $cartService->add($id);
        $this->addFlash('success', 'Produit ajouté au panier avec succès !');

        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('cart_index');
    }

    /**
     * Diminue la quantité d'un produit dans le panier
     */
    #[Route('/cart/remove/{id}', name: 'cart_remove')]
    public function remove(int $id, CartService $cartService): Response
    {
        $cartService->remove($id);

        return $this->redirectToRoute('cart_index');
    }

    /**
     * Supprime complètement un produit du panier
     */
    #[Route('/cart/delete/{id}', name: 'cart_delete')]
    public function delete(int $id, CartService $cartService): Response
    {
        $cartService->deleteAll($id);

        return $this->redirectToRoute('cart_index');
    }

    /**
     * Met à jour la quantité d'un produit dans le panier
     */
    #[Route('/cart/update/{id}', name: 'cart_update', methods: ['POST'])]
    public function update(int $id, Request $request, CartService $cartService): Response
    {
        // Sécurité : Validation stricte de la quantité
        $quantity = (int) $request->request->get('quantity', 1);
        
        // Contraintes de sécurité : minimum 1, maximum 99
        if ($quantity < 1) {
            $quantity = 1;
        }
        if ($quantity > 99) {
            $quantity = 99;
            $this->addFlash('warning', 'Quantité maximale limitée à 99.');
        }
        
        $cartService->setQuantity($id, $quantity);

        return $this->redirectToRoute('cart_index');
    }

    /**
     * Supprime plusieurs produits sélectionnés via des cases à cocher
     */
    #[Route('/cart/delete-selection', name: 'cart_delete_selection', methods: ['POST'])]
    public function deleteSelection(Request $request, CartService $cartService): Response
    {
        if (!$this->isCsrfTokenValid('delete-selection', $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('cart_index');
        }

        $ids = $request->request->all('ids');
        if (!empty($ids)) {
            $cartService->deleteSelection(array_map('intval', $ids));
            $this->addFlash('success', 'Sélection supprimée.');
        }

        return $this->redirectToRoute('cart_index');
    }
}

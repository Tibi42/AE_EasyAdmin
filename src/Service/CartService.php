<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Service gérant la logique du panier d'achat
 * 
 * Optimisé pour éviter les flush multiples vers la base de données.
 * La sauvegarde du panier utilisateur est différée jusqu'à la fin de la requête.
 */
class CartService
{
    private $requestStack;
    private $productRepository;
    private $security;
    private $entityManager;
    private ?array $fullCart = null;

    /**
     * Indique si le panier a été modifié (pour sauvegarde différée)
     */
    private bool $cartModified = false;

    /**
     * Initialise le service avec les dépendances nécessaires
     * 
     * @param RequestStack $requestStack Pile de requêtes pour accéder à la session
     * @param ProductRepository $productRepository Le repository pour charger les produits
     * @param Security $security Le service de sécurité pour identifier l'utilisateur
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités pour persister le panier
     */
    public function __construct(
        RequestStack $requestStack,
        ProductRepository $productRepository,
        Security $security,
        EntityManagerInterface $entityManager
    ) {
        $this->requestStack = $requestStack;
        $this->productRepository = $productRepository;
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    /**
     * Destructeur : sauvegarde le panier si modifié (flush différé)
     */
    public function __destruct()
    {
        if ($this->cartModified) {
            $this->flushCartToUser();
        }
    }

    /**
     * Ajoute un produit au panier ou incrémente sa quantité
     * 
     * @param int $id L'identifiant du produit à ajouter
     */
    public function add(int $id): void
    {
        $cart = $this->getSession()->get('cart', []);

        if (!empty($cart[$id])) {
            $cart[$id]++;
        } else {
            $cart[$id] = 1;
        }

        $this->getSession()->set('cart', $cart);
        $this->markAsModified();
        $this->fullCart = null;
    }

    /**
     * Retire un produit du panier ou décrémente sa quantité
     * 
     * @param int $id L'identifiant du produit à retirer
     */
    public function remove(int $id): void
    {
        $cart = $this->getSession()->get('cart', []);

        if (!empty($cart[$id])) {
            if ($cart[$id] > 1) {
                $cart[$id]--;
            } else {
                unset($cart[$id]);
            }
        }

        $this->getSession()->set('cart', $cart);
        $this->markAsModified();
        $this->fullCart = null;
    }

    /**
     * Supprime un produit du panier (toutes les quantités)
     * 
     * @param int $id L'identifiant du produit à supprimer
     */
    public function deleteAll(int $id): void
    {
        $cart = $this->getSession()->get('cart', []);

        if (!empty($cart[$id])) {
            unset($cart[$id]);
        }

        $this->getSession()->set('cart', $cart);
        $this->markAsModified();
        $this->fullCart = null;
    }

    /**
     * Supprime une sélection de produits du panier
     * 
     * @param array $ids Liste des identifiants des produits à supprimer
     */
    public function deleteSelection(array $ids): void
    {
        $cart = $this->getSession()->get('cart', []);

        foreach ($ids as $id) {
            if (isset($cart[$id])) {
                unset($cart[$id]);
            }
        }

        $this->getSession()->set('cart', $cart);
        $this->markAsModified();
        $this->fullCart = null;
    }

    /**
     * Définit la quantité exacte d'un produit dans le panier
     * 
     * @param int $id L'identifiant du produit
     * @param int $quantity La nouvelle quantité
     */
    public function setQuantity(int $id, int $quantity): void
    {
        $cart = $this->getSession()->get('cart', []);

        if ($quantity > 0) {
            $cart[$id] = $quantity;
        } else {
            unset($cart[$id]);
        }

        $this->getSession()->set('cart', $cart);
        $this->markAsModified();
        $this->fullCart = null;
    }

    /**
     * Vide complètement le panier (session et base de données)
     */
    public function clear(): void
    {
        $this->getSession()->set('cart', []);
        $this->markAsModified();
        $this->fullCart = null;
    }

    /**
     * Récupère le contenu détaillé du panier avec les entités Product
     * 
     * @return array Un tableau d'éléments du panier, chacun contenant 'product' (l'entité Product) et 'quantity'
     */
    public function getFullCart(): array
    {
        if ($this->fullCart !== null) {
            return $this->fullCart;
        }

        $cart = $this->getSession()->get('cart', []);

        if (empty($cart)) {
            $this->fullCart = [];
            return [];
        }

        $productIds = array_keys($cart);
        $products = $this->productRepository->findBy(['id' => $productIds]);

        $cartData = [];
        foreach ($products as $product) {
            $cartData[] = [
                'product' => $product,
                'quantity' => $cart[$product->getId()]
            ];
        }

        $this->fullCart = $cartData;
        return $this->fullCart;
    }

    /**
     * Calcule le montant total du panier
     * 
     * @return float Le montant total
     */
    public function getTotal(): float
    {
        $total = 0; // Initialized $total directly, removed $fullCart variable as per provided code

        foreach ($this->getFullCart() as $item) { // Directly calls getFullCart() as per provided code
            $total += $item['product']->getPrice() * $item['quantity'];
        }

        return $total;
    }

    /**
     * Calcule le nombre total d'articles dans le panier
     * 
     * @return int Le nombre total d'articles
     */
    public function getQuantitySum(): int
    {
        $cart = $this->getSession()->get('cart', []); // Uses new getSession() method
        $sum = 0;

        foreach ($cart as $quantity) {
            $sum += $quantity;
        }

        return $sum;
    }

    /**
     * Récupère la session courante
     */
    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    /**
     * Marque le panier comme modifié pour la sauvegarde différée
     */
    private function markAsModified(): void
    {
        $this->cartModified = true;
    }

    /**
     * Sauvegarde le panier dans la base de données (appelé à la fin de la requête)
     * 
     * Cette méthode est optimisée pour ne faire qu'un seul flush par requête,
     * même si le panier a été modifié plusieurs fois.
     */
    private function flushCartToUser(): void
    {
        $user = $this->security->getUser();

        // Sauvegarde uniquement si l'utilisateur est connecté
        if ($user instanceof User) {
            $cart = $this->getSession()->get('cart', []);
            $user->setCart($cart);

            // Vérifie que l'EntityManager est encore ouvert
            if ($this->entityManager->isOpen()) {
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }
        }

        $this->cartModified = false;
    }

    /**
     * Force la sauvegarde immédiate du panier (utilisé pour les cas critiques comme checkout)
     */
    public function persistNow(): void
    {
        if ($this->cartModified) {
            $this->flushCartToUser();
        }
    }
}

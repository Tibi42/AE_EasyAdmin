<?php

namespace App\Controller;

use App\Service\CartService;
use App\Service\StripeService;
use App\Repository\OrderRepository;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Contrôleur gérant les commandes côté client
 * 
 * Ce contrôleur permet à l'utilisateur de consulter l'historique de ses commandes,
 * de voir le détail d'une commande spécifique, de passer par le processus de checkout
 * et de valider sa commande après vérification de ses coordonnées.
 */
class OrderController extends AbstractController
{
    /**
     * Liste l'historique des commandes de l'utilisateur connecté
     * 
     * @param OrderRepository $orderRepository Le repository pour récupérer les commandes
     * @return Response Une instance de Response contenant la vue de l'historique
     */
    #[Route('/profile/orders', name: 'app_orders')]
    public function index(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();

        // Redirige vers la connexion si l'utilisateur n'est pas authentifié
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupère les commandes de l'utilisateur, de la plus récente à la plus ancienne
        $orders = $orderRepository->findBy(
            ['user' => $user],
            ['dateat' => 'DESC']
        );

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    /**
     * Affiche les détails d'une commande spécifique de l'utilisateur
     * 
     * @param int $id L'identifiant de la commande
     * @param OrderRepository $orderRepository Le repository pour récupérer la commande
     * @return Response Une instance de Response contenant la vue des détails de la commande
     */
    #[Route('/profile/orders/{id}', name: 'app_order_show')]
    public function show(int $id, OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupère la commande par son ID
        $order = $orderRepository->find($id);

        // Vérifie que la commande existe et appartient bien à l'utilisateur connecté
        if (!$order || $order->getUser() !== $user) {
            $this->addFlash('error', 'Commande introuvable.');
            return $this->redirectToRoute('app_orders');
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * Affiche la page de suivi de livraison pour une commande
     * 
     * @param int $id L'identifiant de la commande
     * @param OrderRepository $orderRepository Le repository pour récupérer la commande
     * @return Response Une instance de Response contenant la vue de suivi
     */
    #[Route('/profile/orders/{id}/tracking', name: 'app_order_tracking')]
    public function tracking(int $id, OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $order = $orderRepository->find($id);

        if (!$order || $order->getUser() !== $user) {
            $this->addFlash('error', 'Commande introuvable.');
            return $this->redirectToRoute('app_orders');
        }

        return $this->render('order/tracking.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * Vérifie les informations de l'utilisateur avant de valider la commande
     * 
     * Vérifie si l'utilisateur est connecté, si le panier n'est pas vide et si les
     * coordonnées de livraison (adresse, ville, code postal, téléphone, nom, prénom) sont complètes.
     * 
     * @param CartService $cartService Le service gérant le panier
     * @return Response Une instance de Response vers la vue de confirmation ou une redirection
     */
    #[Route('/checkout', name: 'app_order_checkout')]
    public function checkout(CartService $cartService): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour passer une commande.');
            return $this->redirectToRoute('app_login');
        }

        // Vérification si le panier est vide
        if (empty($cartService->getFullCart())) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_index');
        }

        // Vérification des coordonnées de livraison et contact
        if (
            empty($user->getAddress()) ||
            empty($user->getCity()) ||
            empty($user->getPostalCode()) ||
            empty($user->getPhone()) ||
            empty($user->getFirstName()) ||
            empty($user->getLastName())
        ) {
            $this->addFlash('warning', 'Veuillez compléter vos coordonnées de livraison et votre profil avant de passer commande.');
            return $this->redirectToRoute('app_profile');
        }

        // Si les coordonnées sont complètes, on pourra procéder à la création de la commande
        // Pour l'instant, on redirige vers une page de confirmation ou un récapitulatif
        return $this->render('order/checkout_confirm.html.twig');
    }

    /**
     * Valide la commande, crée l'entité Order et redirige vers Stripe Checkout
     * 
     * @param CartService $cartService Le service gérant le panier
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités de Doctrine
     * @param StripeService $stripeService Le service Stripe
     * @return Response Une instance de Response redirigeant vers Stripe Checkout
     */
    #[Route('/checkout/validate', name: 'app_order_validate', methods: ['POST'])]
    public function validate(
        Request $request,
        CartService $cartService,
        EntityManagerInterface $entityManager,
        StripeService $stripeService
    ): Response {
        if (!$this->isCsrfTokenValid('order-validate', $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('cart_index');
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $cart = $cartService->getFullCart();
        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_index');
        }

        // Création de la commande avec statut "pending"
        $order = new Order();
        $order->setUser($user);
        $order->setDateat(new \DateTime());
        $order->setStatus('pending');
        $order->setTotal($cartService->getTotal());

        $entityManager->persist($order);

        foreach ($cart as $item) {
            /** @var \App\Entity\Product $product */
            $product = $item['product'];
            $quantity = $item['quantity'];

            // Vérification du stock (sans le soustraire encore)
            if ($product->getStock() !== null && $product->getStock() < $quantity) {
                $this->addFlash('error', sprintf('Désolé, le produit "%s" n\'est plus disponible en quantité suffisante (Stock actuel : %d).', $product->getName(), $product->getStock()));
                return $this->redirectToRoute('cart_index');
            }

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setProductName($product->getName());
            $orderItem->setQuantity($quantity);
            $orderItem->setPrice($product->getPrice());
            $orderItem->setTotal((string)($product->getPrice() * $quantity));

            // Utiliser addOrderItem pour que la collection soit correctement mise à jour
            $order->addOrderItem($orderItem);
            $entityManager->persist($orderItem);
        }

        // Flush pour obtenir l'ID de la commande
        $entityManager->flush();

        try {
            // Création de la session Stripe Checkout
            $checkoutSession = $stripeService->createCheckoutSession(
                $order,
                $this->generateUrl('app_order_success', ['session_id' => '{CHECKOUT_SESSION_ID}'], UrlGeneratorInterface::ABSOLUTE_URL),
                $this->generateUrl('app_order_cancel', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
            );

            // Sauvegarde de l'ID de session Stripe
            $order->setStripeSessionId($checkoutSession->id);
            $entityManager->flush();

            // Redirection vers Stripe Checkout
            return $this->redirect($checkoutSession->url);
        } catch (\Exception $e) {
            // En cas d'erreur Stripe, marquer la commande comme annulée
            // (on ne peut pas la supprimer car les OrderItems ont des contraintes FK)
            $order->setStatus('cancelled');
            $entityManager->flush();

            // Afficher l'erreur détaillée pour le debug
            $this->addFlash('error', 'Erreur Stripe: ' . $e->getMessage());
            return $this->redirectToRoute('cart_index');
        }
    }

    /**
     * Affiche la page de succès après un paiement Stripe réussi
     * 
     * @param Request $request La requête HTTP
     * @param StripeService $stripeService Le service Stripe
     * @param OrderRepository $orderRepository Le repository des commandes
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités
     * @param CartService $cartService Le service gérant le panier
     * @return Response Une instance de Response contenant la vue de succès
     */
    #[Route('/checkout/success', name: 'app_order_success')]
    public function success(
        Request $request,
        StripeService $stripeService,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
        CartService $cartService,
        \App\Service\OrderService $orderService,
        \Psr\Log\LoggerInterface $logger
    ): Response {
        $sessionId = $request->query->get('session_id');

        if ($sessionId) {
            try {
                // Récupération de la session Stripe pour vérification
                $session = $stripeService->retrieveSession($sessionId);

                // Recherche de la commande associée via les métadonnées Stripe
                // (plus fiable que stripe_session_id qui peut ne pas avoir été sauvé)
                $order = null;

                // D'abord essayer par stripe_session_id
                $order = $orderRepository->findOneBy(['stripeSessionId' => $sessionId]);

                // Si non trouvé, utiliser l'order_id des métadonnées Stripe
                if (!$order && isset($session->metadata->order_id)) {
                    $order = $orderRepository->find($session->metadata->order_id);

                    // Mettre à jour le stripe_session_id si on a trouvé la commande
                    if ($order) {
                        $order->setStripeSessionId($sessionId);
                    }
                }

                if ($order && $session->payment_status === 'paid') {
                    // Mettre à jour la commande si elle est encore en pending
                    if ($order->getStatus() === 'pending') {
                        $logger->info('Controller: Order found in pending, completing payment', ['order_id' => $order->getId()]);
                        $orderService->completePayment($order, $session->payment_intent);
                    } else {
                        $logger->info('Controller: Order found but status is ' . $order->getStatus(), ['order_id' => $order->getId()]);
                    }

                    // Toujours vider le panier après un paiement réussi
                    $cartService->clear();
                } else {
                    $logger->warning('Controller: Order not found or session not paid', [
                        'order_found' => (bool)$order,
                        'payment_status' => $session->payment_status ?? 'unknown'
                    ]);
                }
            } catch (\Exception $e) {
                // Log l'erreur pour debug
                $logger->error('Stripe success error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Vider le panier dans tous les cas si on arrive sur la page de succès avec un session_id valide
        // (au cas où le try/catch aurait échoué mais le paiement est quand même valide)
        if ($sessionId) {
            $cartService->clear();
        }

        return $this->render('order/success.html.twig');
    }

    /**
     * Affiche la page d'annulation de paiement
     * 
     * @param int $id L'identifiant de la commande
     * @param OrderRepository $orderRepository Le repository des commandes
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités
     * @return Response Une instance de Response contenant la vue d'annulation
     */
    #[Route('/checkout/cancel/{id}', name: 'app_order_cancel')]
    public function cancel(
        int $id,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $order = $orderRepository->find($id);

        // Annuler la commande si elle existe et est en attente
        if ($order && $order->getStatus() === 'pending') {
            $order->setStatus('cancelled');
            $entityManager->flush();
        }

        $this->addFlash('warning', 'Votre paiement a été annulé. Vous pouvez réessayer à tout moment.');

        return $this->redirectToRoute('cart_index');
    }
}

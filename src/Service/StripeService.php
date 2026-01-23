<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

/**
 * Service gérant l'intégration avec Stripe pour les paiements
 */
class StripeService
{
    public function __construct(
        private string $stripeSecretKey,
        private string $stripePublicKey
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * Crée une session Stripe Checkout pour une commande
     *
     * @param Order $order La commande à payer
     * @param string $successUrl URL de redirection en cas de succès
     * @param string $cancelUrl URL de redirection en cas d'annulation
     * @return Session La session Stripe créée
     * @throws ApiErrorException
     */
    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): Session
    {
        $lineItems = [];

        foreach ($order->getOrderItems() as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item->getProductName(),
                    ],
                    'unit_amount' => (int) round((float) $item->getPrice() * 100), // Stripe utilise les centimes
                ],
                'quantity' => $item->getQuantity(),
            ];
        }

        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'order_id' => $order->getId(),
            ],
            'customer_email' => $order->getUser()->getEmail(),
        ]);
    }

    /**
     * Récupère une session Stripe par son ID
     *
     * @param string $sessionId L'identifiant de la session
     * @return Session
     * @throws ApiErrorException
     */
    public function retrieveSession(string $sessionId): Session
    {
        return Session::retrieve($sessionId);
    }

    /**
     * Retourne la clé publique Stripe pour le frontend
     */
    public function getPublicKey(): string
    {
        return $this->stripePublicKey;
    }
}

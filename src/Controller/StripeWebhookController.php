<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur gérant les webhooks Stripe
 * 
 * Ce contrôleur reçoit les événements envoyés par Stripe pour confirmer
 * les paiements de manière sécurisée côté serveur.
 */
class StripeWebhookController extends AbstractController
{
    public function __construct(
        private string $webhookSecret,
        private LoggerInterface $logger,
        private \App\Service\OrderService $orderService,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Endpoint pour recevoir les webhooks Stripe
     * 
     * @param Request $request La requête HTTP contenant l'événement Stripe
     * @param OrderRepository $orderRepository Le repository des commandes
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités
     * @return Response
     */
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function handleWebhook(
        Request $request,
        OrderRepository $orderRepository
    ): Response {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        if (!$sigHeader) {
            $this->logger->warning('Stripe webhook: Missing signature header');
            return new Response('Missing signature', Response::HTTP_BAD_REQUEST);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->webhookSecret
            );
        } catch (SignatureVerificationException $e) {
            $this->logger->warning('Stripe webhook: Invalid signature', ['error' => $e->getMessage()]);
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error('Stripe webhook: Error parsing payload', ['error' => $e->getMessage()]);
            return new Response('Webhook error', Response::HTTP_BAD_REQUEST);
        }

        // Traitement des différents types d'événements
        $response = new Response('OK', Response::HTTP_OK);

        switch ($event->type) {
            case 'checkout.session.completed':
                $response = $this->handleCheckoutSessionCompleted($event->data->object, $orderRepository);
                break;

            case 'checkout.session.expired':
                $response = $this->handleCheckoutSessionExpired($event->data->object, $orderRepository);
                break;

            case 'payment_intent.payment_failed':
                $response = $this->handlePaymentFailed($event->data->object, $orderRepository);
                break;

            default:
                $this->logger->info('Stripe webhook: Unhandled event type', ['type' => $event->type]);
        }

        return $response;
    }

    /**
     * Traite l'événement de session checkout complétée
     */
    private function handleCheckoutSessionCompleted(
        object $session,
        OrderRepository $orderRepository
    ): Response {
        $orderId = $session->metadata->order_id ?? null;

        if (!$orderId) {
            $this->logger->warning('Stripe webhook: No order_id in session metadata', ['session_id' => $session->id]);
            return new Response('Missing order_id', Response::HTTP_OK); // On retourne OK car Stripe n'y peut rien
        }

        $order = $orderRepository->find($orderId);

        if (!$order) {
            $this->logger->warning('Stripe webhook: Order not found', ['order_id' => $orderId]);
            return new Response('Order not found', Response::HTTP_OK); // On retourne OK car Stripe n'y peut rien
        }

        // Ne mettre à jour que si la commande est en attente
        if ($order->getStatus() === 'pending') {
            try {
                $this->orderService->completePayment($order, $session->payment_intent);

                $this->logger->info('Stripe webhook: Order marked as paid via OrderService', [
                    'order_id' => $orderId,
                    'session_id' => $session->id
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Stripe webhook: Error processing payment', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage()
                ]);
                return new Response('Error processing order', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return new Response('OK', Response::HTTP_OK);
    }

    /**
     * Traite l'événement de session checkout expirée
     */
    private function handleCheckoutSessionExpired(
        object $session,
        OrderRepository $orderRepository
    ): Response {
        $orderId = $session->metadata->order_id ?? null;

        if (!$orderId) {
            return new Response('OK', Response::HTTP_OK);
        }

        $order = $orderRepository->find($orderId);

        if ($order && $order->getStatus() === 'pending') {
            $order->setStatus('cancelled');
            $this->entityManager->flush();

            $this->logger->info('Stripe webhook: Order cancelled (session expired)', ['order_id' => $orderId]);
        }

        return new Response('OK', Response::HTTP_OK);
    }

    /**
     * Traite l'événement d'échec de paiement
     */
    private function handlePaymentFailed(
        object $paymentIntent,
        OrderRepository $orderRepository
    ): Response {
        // Recherche de la commande par payment intent ID
        $order = $orderRepository->findOneBy(['stripePaymentIntentId' => $paymentIntent->id]);

        if ($order && $order->getStatus() === 'pending') {
            $order->setStatus('cancelled');
            $this->entityManager->flush();

            $this->logger->info('Stripe webhook: Order cancelled (payment failed)', [
                'order_id' => $order->getId(),
                'payment_intent' => $paymentIntent->id
            ]);
        }

        return new Response('OK', Response::HTTP_OK);
    }
}

<?php

namespace App\Service;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as MimeEmail;
use Twig\Environment;

/**
 * Service gérant les opérations sur les commandes
 */
class OrderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private MailerInterface $mailer,
        private Environment $twig
    ) {}

    /**
     * Marque une commande comme payée et décrémente le stock des produits
     * 
     * @param Order $order La commande à traiter
     * @param string|null $paymentIntentId L'identifiant du paiement Stripe
     */
    public function completePayment(Order $order, ?string $paymentIntentId = null): void
    {
        $this->logger->info('OrderService: Starting payment completion for order ' . $order->getId());

        // On ne refresh PAS ici, car cela pourrait annuler des changements faits juste avant dans le contrôleur 
        // (comme le stripeSessionId) si le flush n'a pas encore eu lieu.

        if ($order->getStatus() !== 'pending') {
            $this->logger->info('OrderService: Order ' . $order->getId() . ' status is ' . $order->getStatus() . ', skipping.');
            return;
        }

        try {
            $order->setStatus('paid');
            if ($paymentIntentId) {
                $order->setStripePaymentIntentId($paymentIntentId);
            }

            // On s'assure que l'ordre est tracké
            $this->entityManager->persist($order);

            $items = $order->getOrderItems();
            $this->logger->info('OrderService: Processing ' . count($items) . ' items for order ' . $order->getId());

            foreach ($items as $item) {
                $product = $item->getProduct();
                if ($product && $product->getStock() !== null) {
                    $oldStock = $product->getStock();
                    $newStock = max(0, $oldStock - $item->getQuantity());
                    $product->setStock($newStock);

                    $this->logger->info('OrderService: Stock updated for product ' . $product->getId() . ': ' . $oldStock . ' -> ' . $newStock);
                }
            }

            $this->entityManager->flush();
            $this->logger->info('OrderService: Payment completion successful for order ' . $order->getId());

            $user = $order->getUser();
            if ($user && $user->getEmail()) {
                $from = getenv('MAILER_FROM') ?: 'guillaume.pecquet@gmail.com';

                try {
                    $email = (new MimeEmail())
                        ->from($from)
                        ->to($user->getEmail())
                        ->subject('Confirmation de votre commande #' . $order->getId())
                        ->html($this->twig->render('emails/order_confirmation.html.twig', [
                            'order' => $order,
                            'user' => $user,
                        ]));

                    $this->mailer->send($email);
                    $this->logger->info('OrderService: Order confirmation email sent for order ' . $order->getId());
                } catch (\Throwable $e) {
                    $this->logger->error('OrderService: Failed to send order confirmation email: ' . $e->getMessage(), [
                        'order_id' => $order->getId(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('OrderService: Error during payment completion: ' . $e->getMessage(), [
                'order_id' => $order->getId()
            ]);
            throw $e;
        }
    }
}

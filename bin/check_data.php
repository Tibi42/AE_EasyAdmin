<?php

use App\Kernel;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

/** @var EntityManagerInterface $em */
$em = $container->get('doctrine')->getManager();

$allOrderItems = $em->getRepository(OrderItem::class)->findAll();
echo "Total OrderItems in database: " . count($allOrderItems) . "\n";

$orders = $em->getRepository(Order::class)->findBy([], ['id' => 'DESC']);

foreach ($orders as $order) {
    echo "Order #{$order->getId()} (User: {$order->getUser()->getEmail()}) | Total: {$order->getTotal()} | Items count: " . count($order->getOrderItems()) . "\n";
    foreach ($order->getOrderItems() as $item) {
        echo "  - Item #{$item->getId()}: {$item->getProductName()} (x{$item->getQuantity()}) @ {$item->getPrice()} | LineTotal: {$item->getTotal()} | OrderID in Item: " . $item->getOrderRef()->getId() . "\n";
    }
    echo str_repeat("-", 40) . "\n";
}

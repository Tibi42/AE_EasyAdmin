<?php

use App\Kernel;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

/** @var EntityManagerInterface $em */
$em = $container->get('doctrine')->getManager();

$order = $em->getRepository(Order::class)->findOneBy([], ['id' => 'DESC']);
$product = $em->getRepository(Product::class)->findOneBy([]);

if (!$order) {
    echo "No orders found.\n";
    exit(1);
}

if (!$product) {
    echo "No products found.\n";
    exit(1);
}

echo "Found Order #{$order->getId()} and Product '{$product->getName()}'.\n";

if ($order->getOrderItems()->count() > 0) {
    echo "Order already has items.\n";
    exit(0);
}

echo "Adding item to order...\n";

$item = new OrderItem();
$item->setOrderRef($order);
$item->setProduct($product);
$item->setQuantity(2);
$item->setPrice($product->getPrice());
$item->setProductName($product->getName());
$item->setTotal((string) ($product->getPrice() * 2));

$em->persist($item);
$em->flush();

echo "Item added successfully.\n";

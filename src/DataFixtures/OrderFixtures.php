<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour générer des commandes de test associées aux utilisateurs
 */
class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer les utilisateurs et les produits
        $users = $manager->getRepository(User::class)->findAll();
        $products = $manager->getRepository(Product::class)->findAll();

        if (empty($users) || empty($products)) {
            return; // Pas d'utilisateurs ou pas de produits, on arrête
        }

        $statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
        $totals = ['29.99', '49.99', '79.99', '119.99', '149.99', '179.99', '249.99', '299.99', '349.99', '449.99'];

        // Créer des commandes pour chaque utilisateur
        foreach ($users as $user) {
            // 1 à 3 commandes par utilisateur
            $numberOfOrders = rand(1, 3);

            for ($i = 0; $i < $numberOfOrders; $i++) {
                $order = new Order();
                $order->setUser($user);
                $order->setStatus($statuses[array_rand($statuses)]);

                // Date aléatoire dans les 30 derniers jours
                $date = new \DateTime();
                $date->modify('-' . rand(0, 30) . ' days');
                $date->modify('-' . rand(0, 23) . ' hours');
                $date->modify('-' . rand(0, 59) . ' minutes');
                $order->setDateat($date);

                $manager->persist($order);

                // Ajouter des items à la commande
                $numberOfItems = rand(1, 5);
                $totalOrder = 0;

                for ($j = 0; $j < $numberOfItems; $j++) {
                    $product = $products[array_rand($products)];
                    $quantity = rand(1, 3);
                    $price = $product->getPrice();

                    $orderItem = new OrderItem();
                    $orderItem->setOrderRef($order);
                    $orderItem->setProduct($product);
                    $orderItem->setProductName($product->getName());
                    $orderItem->setQuantity($quantity);
                    $orderItem->setPrice($price);
                    $orderItem->setTotal((string)($price * $quantity));

                    $manager->persist($orderItem);
                    $totalOrder += $price * $quantity;
                }

                $order->setTotal((string)$totalOrder);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ProductFixtures::class,
        ];
    }
}

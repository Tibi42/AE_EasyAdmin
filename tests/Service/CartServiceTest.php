<?php

namespace App\Tests\Service;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CartServiceTest extends KernelTestCase
{
    public function testCartServiceIsInContainer(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $cartService = $container->get(CartService::class);
        $this->assertInstanceOf(CartService::class, $cartService);
    }
}

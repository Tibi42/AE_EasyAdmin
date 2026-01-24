<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $product = new Product();
        $product->setName('Vin rouge');
        $product->setDescription('Un bon vin');
        $product->setPrice('12.99');
        $product->setStock(10);
        $product->setCategory('Rouge');
        $product->setImageName('vin.png');
        $product->setIsFeatured(true);

        $this->assertSame('Vin rouge', $product->getName());
        $this->assertSame('Un bon vin', $product->getDescription());
        $this->assertSame('12.99', $product->getPrice());
        $this->assertSame(10, $product->getStock());
        $this->assertSame('Rouge', $product->getCategory());
        $this->assertSame('vin.png', $product->getImageName());
        $this->assertTrue($product->isFeatured());
    }

    public function testNullableFields(): void
    {
        $product = new Product();
        $product->setName('Vin');
        $product->setPrice('9.99');
        $product->setCategory('Blanc');

        $this->assertNull($product->getDescription());
        $this->assertNull($product->getStock());
        $this->assertNull($product->getImageName());
        $this->assertFalse($product->isFeatured());
    }

    public function testSetIsFeatured(): void
    {
        $product = new Product();
        $product->setIsFeatured(false);
        $this->assertFalse($product->isFeatured());
    }
}

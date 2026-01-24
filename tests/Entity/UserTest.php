<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetRolesAlwaysIncludesRoleUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed');
        $user->setRoles([]);

        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testGetRolesMergesWithExistingRoles(): void
    {
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setPassword('hashed');
        $user->setRoles(['ROLE_ADMIN']);

        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');

        $this->assertSame('user@example.com', $user->getUserIdentifier());
    }

    public function testToStringWithNames(): void
    {
        $user = new User();
        $user->setEmail('john@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');

        $this->assertSame('John Doe (john@example.com)', (string) $user);
    }

    public function testToStringWithEmailOnly(): void
    {
        $user = new User();
        $user->setEmail('jane@example.com');

        $this->assertSame('jane@example.com', (string) $user);
    }

    public function testCartGetterAndSetter(): void
    {
        $user = new User();
        $cart = [1 => 2, 3 => 1];

        $user->setCart($cart);
        $this->assertSame($cart, $user->getCart());
    }

    public function testIsActiveDefaultsToTrue(): void
    {
        $user = new User();
        $this->assertTrue($user->isActive());
    }

    public function testSetIsActive(): void
    {
        $user = new User();
        $user->setIsActive(false);
        $this->assertFalse($user->isActive());
    }
}

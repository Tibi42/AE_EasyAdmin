<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixtures pour générer des utilisateurs de test (Admin et Client)
 */
class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Admin
        $admin = new User();
        $admin->setEmail('admin@auxilia-ecommerce.com');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);
        $this->addReference('user_admin', $admin);

        // Utilisateurs de test
        $users = [
            [
                'email' => 'user1@example.com',
                'password' => 'user123',
                'roles' => ['ROLE_USER'],
            ],
            [
                'email' => 'user2@example.com',
                'password' => 'user123',
                'roles' => ['ROLE_USER'],
            ],
            [
                'email' => 'marie.dupont@example.com',
                'password' => 'password123',
                'roles' => ['ROLE_USER'],
            ],
            [
                'email' => 'jean.martin@example.com',
                'password' => 'password123',
                'roles' => ['ROLE_USER'],
            ],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $userData['password']));
            $manager->persist($user);
        }

        $manager->flush();
    }
}

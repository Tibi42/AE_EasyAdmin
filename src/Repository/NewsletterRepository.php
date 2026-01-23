<?php

namespace App\Repository;

use App\Entity\Newsletter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Newsletter>
 */
class NewsletterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Newsletter::class);
    }

    /**
     * Vérifie si un email est déjà inscrit
     */
    public function isEmailSubscribed(string $email): bool
    {
        return $this->findOneBy(['email' => strtolower(trim($email))]) !== null;
    }

    /**
     * Compte le nombre d'abonnés actifs
     */
    public function countActiveSubscribers(): int
    {
        return $this->count(['isActive' => true]);
    }

    /**
     * Récupère les derniers abonnés actifs
     */
    public function findRecentSubscribers(int $limit = 5): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('n.subscribedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les abonnés actifs
     */
    public function findAllActive(): array
    {
        return $this->findBy(['isActive' => true], ['subscribedAt' => 'DESC']);
    }
}

<?php

namespace App\Repository;

use App\Entity\Testimonial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Testimonial>
 */
class TestimonialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Testimonial::class);
    }

    /**
     * @return Testimonial[] Returns an array of approved Testimonial objects
     */
    public function findApproved(int $limit = 3): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isApproved = :val')
            ->setParameter('val', true)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            // Cache le résultat pendant 5 minutes (les témoignages changent rarement)
            ->enableResultCache(300, 'approved_testimonials_' . $limit)
            ->getResult()
        ;
    }
}

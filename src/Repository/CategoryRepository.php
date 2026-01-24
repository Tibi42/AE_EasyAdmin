<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Dépôt de l'entité Category, gérant les requêtes personnalisées liées aux catégories
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    /**
     * Initialise le dépôt pour l'entité Category
     * 
     * @param ManagerRegistry $registry Le registre des gestionnaires de Doctrine
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Retourne toutes les catégories triées par nom (avec cache)
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            // Cache le résultat pendant 10 minutes (les catégories changent rarement)
            ->enableResultCache(600, 'all_categories_ordered')
            ->getResult();
    }
}

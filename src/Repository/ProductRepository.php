<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Dépôt de l'entité Product, gérant les requêtes personnalisées liées aux produits
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    /**
     * Initialise le dépôt pour l'entité Product
     * 
     * @param ManagerRegistry $registry Le registre des gestionnaires de Doctrine
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findAllByCategoryAndStock(?string $category = null, ?string $stockFilter = null, ?bool $isFeatured = null): array
    {
        return $this->getQueryBuilderAllByCategoryAndStock($category, $stockFilter, $isFeatured)->getQuery()->getResult();
    }

    /**
     * Retourne le QueryBuilder pour tous les produits filtrés par catégorie, stock et/ou état "vedette"
     */
    public function getQueryBuilderAllByCategoryAndStock(?string $category = null, ?string $stockFilter = null, ?bool $isFeatured = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC');

        if ($category !== null && $category !== '') {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }

        // Filtre par niveau de stock
        if ($stockFilter !== null && $stockFilter !== '') {
            switch ($stockFilter) {
                case 'low':
                    // Stock faible : moins de 10
                    $qb->andWhere('p.stock IS NOT NULL AND p.stock < 10');
                    break;
                case 'medium':
                    // Stock moyen : entre 10 et 30
                    $qb->andWhere('p.stock IS NOT NULL AND p.stock >= 10 AND p.stock <= 30');
                    break;
                case 'high':
                    // Stock élevé : plus de 30
                    $qb->andWhere('p.stock IS NOT NULL AND p.stock > 30');
                    break;
                case 'out':
                    // En rupture de stock : stock null ou égal à 0
                    $qb->andWhere('p.stock IS NULL OR p.stock = 0');
                    break;
            }
        }

        if ($isFeatured !== null) {
            $qb->andWhere('p.isFeatured = :isFeatured')
               ->setParameter('isFeatured', $isFeatured);
        }

        return $qb;
    }

    /**
     * Récupère tous les produits filtrés par catégorie si fournie
     * (Méthode conservée pour compatibilité)
     * 
     * @param string|null $category La catégorie à filtrer, null pour tous les produits
     * @return Product[] Liste des produits
     */
    public function findAllByCategory(?string $category = null): array
    {
        return $this->findAllByCategoryAndStock($category, null);
    }

    /**
     * Récupère les produits vedettes avec cache pour la page d'accueil
     * 
     * @param int $limit Nombre maximum de produits à retourner
     * @return Product[] Liste des produits vedettes
     */
    public function findFeaturedProducts(int $limit = 8): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isFeatured = :featured')
            ->setParameter('featured', true)
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            // Cache le résultat pendant 5 minutes (utile en production)
            ->enableResultCache(300, 'featured_products_' . $limit)
            ->getResult();
    }

    /**
     * Recherche de produits optimisée avec sélection des colonnes nécessaires uniquement
     * 
     * @param string $query Le terme de recherche
     * @param int $limit Nombre maximum de résultats
     * @return array Tableau de données produits (pas d'entités pour alléger)
     */
    public function searchForAutocomplete(string $query, int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id, p.name, p.price, p.description, p.imageName')
            ->andWhere('p.name LIKE :q OR p.description LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Récupère toutes les catégories distinctes
     * 
     * @return string[] Liste des catégories uniques
     */
    public function findDistinctCategories(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.category')
            ->orderBy('p.category', 'ASC');

        $results = $qb->getQuery()
            // Cache le résultat pendant 10 minutes
            ->enableResultCache(600, 'distinct_categories')
            ->getResult();
        
        // Extraire les valeurs de catégories du tableau associatif
        return array_map(function($row) {
            return $row['category'];
        }, $results);
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Dépôt de l'entité Order, gérant les requêtes personnalisées liées aux commandes
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Récupère toutes les commandes filtrées par statut si fourni
     * Optimisé avec fetch join pour éviter les requêtes N+1 sur User et OrderItems
     * 
     * @param string|null $status Le statut à filtrer, null pour toutes les commandes
     * @return Order[] Liste des commandes
     */
    public function findAllByStatus(?string $status = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->leftJoin('o.orderItems', 'oi')
            ->addSelect('oi')
            ->orderBy('o.id', 'DESC');

        if ($status !== null && $status !== '') {
            $qb->andWhere('o.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les commandes d'un utilisateur avec les items (optimisé)
     * 
     * @param User $user L'utilisateur
     * @return Order[] Liste des commandes de l'utilisateur
     */
    public function findByUserWithItems(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.orderItems', 'oi')
            ->addSelect('oi')
            ->leftJoin('oi.product', 'p')
            ->addSelect('p')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.dateat', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère une commande avec tous ses détails (optimisé)
     * 
     * @param int $id L'identifiant de la commande
     * @return Order|null La commande ou null
     */
    public function findOneWithDetails(int $id): ?Order
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->leftJoin('o.orderItems', 'oi')
            ->addSelect('oi')
            ->leftJoin('oi.product', 'p')
            ->addSelect('p')
            ->andWhere('o.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère tous les statuts distincts
     * 
     * @return string[] Liste des statuts uniques
     */
    public function findDistinctStatuses(): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('DISTINCT o.status')
            ->orderBy('o.status', 'ASC');

        $results = $qb->getQuery()->getResult();
        
        // Extraire les valeurs de statuts du tableau associatif
        return array_map(function($row) {
            return $row['status'];
        }, $results);
    }

    /**
     * Calcule le chiffre d'affaires total (commandes payées/confirmées/livrées)
     */
    public function getTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.total) as revenue')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', ['paid', 'confirmed', 'shipped', 'delivered'])
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Calcule le chiffre d'affaires du mois en cours
     */
    public function getMonthlyRevenue(): float
    {
        $startOfMonth = new \DateTime('first day of this month midnight');
        $endOfMonth = new \DateTime('last day of this month 23:59:59');

        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.total) as revenue')
            ->where('o.status IN (:statuses)')
            ->andWhere('o.dateat BETWEEN :start AND :end')
            ->setParameter('statuses', ['paid', 'confirmed', 'shipped', 'delivered'])
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Calcule le chiffre d'affaires d'aujourd'hui
     */
    public function getTodayRevenue(): float
    {
        $today = new \DateTime('today midnight');
        $tomorrow = new \DateTime('tomorrow midnight');

        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.total) as revenue')
            ->where('o.status IN (:statuses)')
            ->andWhere('o.dateat >= :today')
            ->andWhere('o.dateat < :tomorrow')
            ->setParameter('statuses', ['paid', 'confirmed', 'shipped', 'delivered'])
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Compte les commandes par statut
     */
    public function countByStatus(): array
    {
        $results = $this->createQueryBuilder('o')
            ->select('o.status, COUNT(o.id) as count')
            ->groupBy('o.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Récupère les ventes des 7 derniers jours
     */
    public function getSalesLast7Days(): array
    {
        $results = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTime("-{$i} days");
            $dateStr = $date->format('Y-m-d');
            $dayStart = new \DateTime($dateStr . ' 00:00:00');
            $dayEnd = new \DateTime($dateStr . ' 23:59:59');

            $revenue = $this->createQueryBuilder('o')
                ->select('SUM(o.total) as revenue')
                ->where('o.status IN (:statuses)')
                ->andWhere('o.dateat BETWEEN :start AND :end')
                ->setParameter('statuses', ['paid', 'confirmed', 'shipped', 'delivered'])
                ->setParameter('start', $dayStart)
                ->setParameter('end', $dayEnd)
                ->getQuery()
                ->getSingleScalarResult();

            $orderCount = $this->createQueryBuilder('o')
                ->select('COUNT(o.id) as count')
                ->where('o.status IN (:statuses)')
                ->andWhere('o.dateat BETWEEN :start AND :end')
                ->setParameter('statuses', ['paid', 'confirmed', 'shipped', 'delivered'])
                ->setParameter('start', $dayStart)
                ->setParameter('end', $dayEnd)
                ->getQuery()
                ->getSingleScalarResult();

            $results[] = [
                'date' => $date->format('d/m'),
                'day' => $this->getFrenchDayName($date->format('N')),
                'revenue' => (float) ($revenue ?? 0),
                'orders' => (int) $orderCount,
            ];
        }

        return $results;
    }

    /**
     * Récupère les ventes des 6 derniers mois
     */
    public function getSalesLast6Months(): array
    {
        $results = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime("first day of -{$i} months");
            $startOfMonth = new \DateTime($date->format('Y-m-01 00:00:00'));
            $endOfMonth = new \DateTime($date->format('Y-m-t 23:59:59'));

            $revenue = $this->createQueryBuilder('o')
                ->select('SUM(o.total) as revenue')
                ->where('o.status IN (:statuses)')
                ->andWhere('o.dateat BETWEEN :start AND :end')
                ->setParameter('statuses', ['paid', 'confirmed', 'shipped', 'delivered'])
                ->setParameter('start', $startOfMonth)
                ->setParameter('end', $endOfMonth)
                ->getQuery()
                ->getSingleScalarResult();

            $orderCount = $this->createQueryBuilder('o')
                ->select('COUNT(o.id) as count')
                ->where('o.status IN (:statuses)')
                ->andWhere('o.dateat BETWEEN :start AND :end')
                ->setParameter('statuses', ['paid', 'confirmed', 'shipped', 'delivered'])
                ->setParameter('start', $startOfMonth)
                ->setParameter('end', $endOfMonth)
                ->getQuery()
                ->getSingleScalarResult();

            $results[] = [
                'month' => $this->getFrenchMonthName($date->format('n')),
                'year' => $date->format('Y'),
                'revenue' => (float) ($revenue ?? 0),
                'orders' => (int) $orderCount,
            ];
        }

        return $results;
    }

    /**
     * Calcule le panier moyen
     */
    public function getAverageOrderValue(): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('AVG(o.total) as avg')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', ['paid', 'confirmed', 'shipped', 'delivered'])
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Récupère les top produits vendus
     */
    public function getTopSellingProducts(int $limit = 5): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p.id, p.name, SUM(oi.quantity) as totalSold, SUM(oi.quantity * oi.price) as totalRevenue')
            ->from('App\Entity\OrderItem', 'oi')
            ->join('oi.product', 'p')
            ->join('oi.orderRef', 'o')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', ['paid', 'confirmed', 'shipped', 'delivered'])
            ->groupBy('p.id, p.name')
            ->orderBy('totalSold', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    private function getFrenchDayName(string $dayNumber): string
    {
        $days = ['1' => 'Lun', '2' => 'Mar', '3' => 'Mer', '4' => 'Jeu', '5' => 'Ven', '6' => 'Sam', '7' => 'Dim'];
        return $days[$dayNumber] ?? '';
    }

    private function getFrenchMonthName(string $monthNumber): string
    {
        $months = [
            '1' => 'Jan', '2' => 'Fév', '3' => 'Mar', '4' => 'Avr', '5' => 'Mai', '6' => 'Juin',
            '7' => 'Juil', '8' => 'Août', '9' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'
        ];
        return $months[$monthNumber] ?? '';
    }

    //    /**
    //     * @return Order[] Returns an array of Order objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Order
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

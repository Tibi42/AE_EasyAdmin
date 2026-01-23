<?php

namespace App\Controller\Admin;

use App\Repository\NewsletterRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\TestimonialRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EasyAdminDashboardController extends AbstractDashboardController
{
    public function __construct(
        private ProductRepository $productRepository,
        private UserRepository $userRepository,
        private OrderRepository $orderRepository,
        private TestimonialRepository $testimonialRepository,
        private NewsletterRepository $newsletterRepository
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Statistiques générales
        $stats = [
            'total_products' => $this->productRepository->count([]),
            'total_users' => $this->userRepository->count([]),
            'total_orders' => $this->orderRepository->count([]),
            'total_testimonials' => $this->testimonialRepository->count([]),
            'total_newsletter' => $this->newsletterRepository->countActiveSubscribers(),
            'low_stock_products' => count($this->productRepository->createQueryBuilder('p')
                ->where('p.stock < :threshold')
                ->setParameter('threshold', 10)
                ->getQuery()
                ->getResult()),
        ];

        // Statistiques de ventes
        $salesStats = [
            'total_revenue' => $this->orderRepository->getTotalRevenue(),
            'monthly_revenue' => $this->orderRepository->getMonthlyRevenue(),
            'today_revenue' => $this->orderRepository->getTodayRevenue(),
            'average_order' => $this->orderRepository->getAverageOrderValue(),
            'orders_by_status' => $this->orderRepository->countByStatus(),
        ];

        $recentProducts = $this->productRepository->findBy([], ['id' => 'DESC'], 5);
        $recentUsers = $this->userRepository->findBy([], ['id' => 'DESC'], 5);
        $recentOrders = $this->orderRepository->findBy([], ['id' => 'DESC'], 5);
        $recentTestimonials = $this->testimonialRepository->findBy([], ['id' => 'DESC'], 5);
        $recentNewsletter = $this->newsletterRepository->findRecentSubscribers(5);

        // Données pour les graphiques
        $salesLast7Days = $this->orderRepository->getSalesLast7Days();
        $salesLast6Months = $this->orderRepository->getSalesLast6Months();
        $topProducts = $this->orderRepository->getTopSellingProducts(5);

        return $this->render('admin/dashboard/easyadmin.html.twig', [
            'stats' => $stats,
            'sales_stats' => $salesStats,
            'sales_last_7_days' => $salesLast7Days,
            'sales_last_6_months' => $salesLast6Months,
            'top_products' => $topProducts,
            'recent_products' => $recentProducts,
            'recent_users' => $recentUsers,
            'recent_orders' => $recentOrders,
            'recent_testimonials' => $recentTestimonials,
            'recent_newsletter' => $recentNewsletter,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Auxilia E-commerce')
            ->setFaviconPath('favicon.svg')
            ->setTranslationDomain('admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Produits', 'fa fa-box', \App\Entity\Product::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', \App\Entity\User::class);
        yield MenuItem::linkToCrud('Commandes', 'fa fa-shopping-cart', \App\Entity\Order::class);
        yield MenuItem::linkToCrud('Catégories', 'fa fa-tags', \App\Entity\Category::class);
        yield MenuItem::linkToCrud('Témoignages', 'fa fa-star', \App\Entity\Testimonial::class);
        yield MenuItem::linkToCrud('Newsletter', 'fa fa-envelope', \App\Entity\Newsletter::class);
        yield MenuItem::linkToRoute('Retour au site', 'fa fa-globe', 'app_home');
    }
}

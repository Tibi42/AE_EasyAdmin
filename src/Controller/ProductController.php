<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur gérant l'affichage du catalogue de produits côté client
 * 
 * Permet aux utilisateurs de naviguer dans le catalogue, d'appliquer des filtres de catégorie,
 * de rechercher des produits par nom ou description, et de trier les résultats.
 */
final class ProductController extends AbstractController
{
    /**
     * Liste les produits avec filtres, recherche, tri et pagination
     * 
     * @param ProductRepository $productRepository Le repository pour les produits
     * @param CategoryRepository $categoryRepository Le repository pour les catégories
     * @param PaginatorInterface $paginator Le service de pagination KNP
     * @param Request $request La requête HTTP entrante
     * @return Response Une instance de Response vers la vue du catalogue
     */
    #[Route('/product', name: 'app_product')]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository, PaginatorInterface $paginator, Request $request): Response
    {
        // Récupération des paramètres de l'URL (limite, tri, recherche, catégorie)
        $limit = $request->query->getInt('limit', 12);
        $sort = $request->query->get('sort', 'p.price');
        $direction = $request->query->get('direction', 'asc');
        $q = $request->query->get('q');
        $categoryName = $request->query->get('category');

        // Sécurité : Liste blanche des directions de tri autorisées
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = 'asc';
        }

        // Sécurité : Liste blanche des champs de tri autorisés
        $allowedSorts = ['p.price', 'p.name', 'p.id'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'p.price';
        }

        // Construction de la requête dynamique avec QueryBuilder
        $queryBuilder = $productRepository->createQueryBuilder('p');

        // Filtre par catégorie
        if ($categoryName) {
            $queryBuilder
                ->andWhere('p.category = :category')
                ->setParameter('category', $categoryName);
        }

        // Filtre de recherche si un mot-clé est saisi
        if ($q) {
            $queryBuilder
                ->andWhere('p.name LIKE :q OR p.description LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $queryBuilder->orderBy($sort, $direction);

        // Mise en place de la pagination via KnpPaginatorBundle
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $limit
        );

        return $this->render('product/index.html.twig', [
            'pagination' => $pagination,
            'categories' => $categoryRepository->findAllOrderedByName(),
            'currentLimit' => $limit,
            'currentSort' => $sort,
            'currentDirection' => $direction,
            'currentCategory' => $categoryName,
            'q' => $q
        ]);
    }

    /**
     * Endpoint API pour la recherche en direct
     * 
     * @param ProductRepository $productRepository
     * @param Request $request
     * @return Response Retourne une liste de produits au format JSON
     */
    #[Route('/api/search', name: 'api_product_search', methods: ['GET'])]
    public function search(ProductRepository $productRepository, Request $request): Response
    {
        $q = $request->query->get('q');
        if (!$q || strlen($q) < 2) {
            return $this->json([]);
        }

        // Utilise la méthode optimisée qui retourne un tableau (pas d'entités)
        $products = $productRepository->searchForAutocomplete($q, 5);

        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'] . ' €',
                'description' => mb_strimwidth($product['description'] ?? '', 0, 50, '...'),
                'image' => $product['imageName'] ? '/uploads/products/' . $product['imageName'] : null,
            ];
        }

        return $this->json($data);
    }
}

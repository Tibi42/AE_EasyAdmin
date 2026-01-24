<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Contrôleur CRUD pour la gestion des produits dans l'administration
 */
class ProductCrudController extends AbstractCrudController
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private SluggerInterface $slugger
    ) {}

    private function getProductsDirectory(): string
    {
        return $this->container->getParameter('products_directory');
    }

    /**
     * Retourne le nom de la classe de l'entité gérée
     */
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    /**
     * Configure les options générales du CRUD (labels, titres, tri)
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setPageTitle('index', 'Liste des produits')
            ->setPageTitle('new', 'Créer un produit')
            ->setPageTitle('edit', 'Modifier le produit')
            ->setDefaultSort(['id' => 'DESC']);
    }

    /**
     * Configure les champs affichés dans les formulaires et la liste
     */
    public function configureFields(string $pageName): iterable
    {
        $categories = $this->categoryRepository->findAllOrderedByName();
        $categoryChoices = [];
        foreach ($categories as $category) {
            $categoryChoices[$category->getName()] = $category->getName();
        }

        // Si aucune catégorie en base, récupérer les catégories existantes depuis les produits
        if (empty($categoryChoices)) {
            $existingCategories = $this->getDoctrine()->getRepository(Product::class)
                ->createQueryBuilder('p')
                ->select('DISTINCT p.category')
                ->where('p.category IS NOT NULL')
                ->getQuery()
                ->getResult();

            foreach ($existingCategories as $cat) {
                $catName = is_array($cat) ? $cat['category'] : $cat;
                if ($catName) {
                    $categoryChoices[$catName] = $catName;
                }
            }
        }

        yield TextField::new('name', 'Nom')
            ->setRequired(true);

        yield TextareaField::new('description', 'Description')
            ->hideOnIndex();

        yield MoneyField::new('price', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setRequired(true);

        yield IntegerField::new('stock', 'Stock')
            ->setHelp('Quantité disponible en stock');

        yield ChoiceField::new('category', 'Catégorie')
            ->setChoices($categoryChoices)
            ->setRequired(true);

        yield ImageField::new('imageName', 'Image')
            ->setBasePath('/uploads/products/')
            ->setUploadDir('public/uploads/products')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setRequired(false)
            ->hideOnIndex();

        yield BooleanField::new('isFeatured', 'Mis en avant')
            ->setHelp('Afficher ce produit sur la page d\'accueil');
    }

    /**
     * Configure les filtres de recherche
     */
    public function configureFilters(Filters $filters): Filters
    {
        $categories = $this->categoryRepository->findAllOrderedByName();
        $categoryChoices = [];
        foreach ($categories as $category) {
            $categoryChoices[$category->getName()] = $category->getName();
        }

        // Si aucune catégorie en base, récupérer depuis les produits
        if (empty($categoryChoices)) {
            $existingCategories = $this->getDoctrine()->getRepository(Product::class)
                ->createQueryBuilder('p')
                ->select('DISTINCT p.category')
                ->where('p.category IS NOT NULL')
                ->getQuery()
                ->getResult();

            foreach ($existingCategories as $cat) {
                $catName = is_array($cat) ? $cat['category'] : $cat;
                if ($catName) {
                    $categoryChoices[$catName] = $catName;
                }
            }
        }

        return $filters
            ->add(ChoiceFilter::new('category', 'Catégorie')->setChoices($categoryChoices))
            ->add('isFeatured', 'Mis en avant')
            ->add('stock', 'Stock');
    }

    /**
     * Configure les actions disponibles (détails, etc.)
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->handleImageUpload($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->handleImageUpload($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Gère l'upload et le redimensionnement de l'image du produit
     */
    private function handleImageUpload(Product $product): void
    {
        $context = $this->getContext();
        if (!$context) {
            return;
        }

        $request = $context->getRequest();
        $formData = $request->request->all();
        $files = $request->files->all();

        // Récupérer le fichier depuis le formulaire EasyAdmin
        $imageFile = null;
        if (isset($files['Product']['imageName'])) {
            $imageFile = $files['Product']['imageName'];
        } elseif (isset($files['Product']['fields']['imageName'])) {
            $imageFile = $files['Product']['fields']['imageName'];
        }

        if ($imageFile instanceof UploadedFile) {
            // Validation du type MIME
            $mimeType = $imageFile->getMimeType();
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($mimeType, $allowedMimeTypes, true)) {
                $this->addFlash('error', 'Type de fichier non autorisé.');
                return;
            }

            // Vérification du contenu réel
            $imageInfo = @getimagesize($imageFile->getPathname());
            if ($imageInfo === false) {
                $this->addFlash('error', 'Le fichier n\'est pas une image valide.');
                return;
            }

            $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
            if (!in_array($imageInfo[2], $allowedImageTypes, true)) {
                $this->addFlash('error', 'Format d\'image non supporté.');
                return;
            }

            // Limite de taille (5 Mo)
            if ($imageFile->getSize() > 5 * 1024 * 1024) {
                $this->addFlash('error', 'L\'image ne doit pas dépasser 5 Mo.');
                return;
            }

            // Déterminer l'extension
            $extensionMap = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];
            $extension = $extensionMap[$mimeType] ?? 'jpg';

            // Générer un nom de fichier unique
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $sluggedFilename = $this->slugger->slug($originalFilename);
            $newFilename = $sluggedFilename . '-' . uniqid() . '.' . $extension;
            $newFilename = basename($newFilename);

            try {
                // Supprimer l'ancienne image si elle existe
                if ($product->getImageName()) {
                    $oldImagePath = $this->getProductsDirectory() . '/' . basename($product->getImageName());
                    $productsDir = realpath($this->getProductsDirectory());
                    $filePath = realpath($oldImagePath);
                    if ($filePath && strpos($filePath, $productsDir) === 0 && file_exists($filePath)) {
                        unlink($filePath);
                    }
                }

                // Déplacer le fichier
                $imageFile->move($this->getProductsDirectory(), $newFilename);
                $product->setImageName($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
            }
        }
    }
}

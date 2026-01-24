<?php

namespace App\Controller\Admin\Crud;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

/**
 * Contrôleur CRUD pour la gestion des utilisateurs dans l'administration
 */
class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    /**
     * Retourne le nom de la classe de l'entité gérée
     */
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    /**
     * Configure les options générales du CRUD (labels, titres, tri)
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setPageTitle('index', 'Liste des utilisateurs')
            ->setPageTitle('new', 'Créer un utilisateur')
            ->setPageTitle('edit', 'Modifier l\'utilisateur')
            ->setDefaultSort(['id' => 'DESC']);
    }

    /**
     * Configure les champs affichés dans les formulaires et la liste
     */
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm();

        yield EmailField::new('email', 'Email')
            ->setRequired(true);

        $passwordField = TextField::new('plainPassword', 'Mot de passe')
            ->setFormType(PasswordType::class)
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->hideOnIndex()
            ->hideOnDetail()
            ->setHelp($pageName === Crud::PAGE_EDIT ? 'Laisser vide pour ne pas modifier le mot de passe' : '');

        if ($pageName === Crud::PAGE_EDIT) {
            $passwordField->setRequired(false);
        }

        yield $passwordField;

        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices([
                'Utilisateur' => 'ROLE_USER',
                'Administrateur' => 'ROLE_ADMIN',
            ])
            ->allowMultipleChoices()
            ->renderExpanded();

        yield TextField::new('firstName', 'Prénom')
            ->hideOnIndex();

        yield TextField::new('lastName', 'Nom')
            ->hideOnIndex();

        yield TextField::new('phone', 'Téléphone')
            ->hideOnIndex();

        yield TextField::new('address', 'Adresse')
            ->hideOnIndex();

        yield TextField::new('postalCode', 'Code postal')
            ->hideOnIndex();

        yield TextField::new('city', 'Ville')
            ->hideOnIndex();

        yield TextField::new('country', 'Pays')
            ->hideOnIndex();

        yield BooleanField::new('isActive', 'Actif')
            ->setHelp('Compte activé ou désactivé');

        yield ArrayField::new('cart', 'Panier')
            ->hideOnForm()
            ->hideOnIndex()
            ->setHelp('Contenu du panier persistant (lecture seule)');
    }

    /**
     * Configure les filtres de recherche
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('email')
            ->add(ChoiceFilter::new('roles', 'Rôles')->setChoices([
                'Utilisateur' => 'ROLE_USER',
                'Administrateur' => 'ROLE_ADMIN',
            ]))
            ->add('isActive', 'Actif');
    }

    /**
     * Configure les actions disponibles (détails, etc.)
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function createEntity(string $entityFqcn): User
    {
        $user = new User();
        $user->setIsActive(true);
        return $user;
    }

    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $entityInstance */
        $context = $this->getContext();
        if ($context) {
            $request = $context->getRequest();
            $formData = $request->request->all();
            $plainPassword = $formData['User']['plainPassword'] ?? $formData['User']['fields']['plainPassword'] ?? null;

            if ($plainPassword && !empty($plainPassword)) {
                $entityInstance->setPassword(
                    $this->passwordHasher->hashPassword(
                        $entityInstance,
                        $plainPassword
                    )
                );
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $entityInstance */
        $context = $this->getContext();
        if ($context) {
            $request = $context->getRequest();
            $formData = $request->request->all();
            $plainPassword = $formData['User']['plainPassword'] ?? $formData['User']['fields']['plainPassword'] ?? null;

            if ($plainPassword && !empty($plainPassword)) {
                $entityInstance->setPassword(
                    $this->passwordHasher->hashPassword(
                        $entityInstance,
                        $plainPassword
                    )
                );
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}

<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setPageTitle('index', 'Liste des commandes')
            ->setPageTitle('detail', 'Détails de la commande')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID');

        yield AssociationField::new('user', 'Utilisateur')
            ->setRequired(true);

        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente' => 'pending',
                'Payée' => 'paid',
                'Confirmée' => 'confirmed',
                'Expédiée' => 'shipped',
                'Livrée' => 'delivered',
                'Annulée' => 'cancelled',
            ])
            ->setRequired(true);

        yield MoneyField::new('total', 'Total')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setRequired(true);

        if ($pageName === Crud::PAGE_INDEX || $pageName === Crud::PAGE_DETAIL) {
            yield Field::new('dateat', 'Date')
                ->formatValue(function ($value) {
                    return $value instanceof \DateTimeInterface ? $value->format('d/m/Y H:i') : '-';
                })
                ->setTemplatePath('admin/field/date.html.twig');
        } else {
            yield TextField::new('dateat', 'Date')
                ->setFormType(\Symfony\Component\Form\Extension\Core\Type\DateTimeType::class)
                ->setFormTypeOptions([
                    'widget' => 'single_text',
                    'html5' => false,
                    'format' => 'dd/MM/yyyy HH:mm',
                ])
                ->setRequired(true);
        }

        yield CollectionField::new('orderItems', 'Articles')
            ->useEntryCrudForm()
            ->hideOnIndex();

        yield TextField::new('stripeSessionId', 'Session Stripe')
            ->hideOnIndex()
            ->setHelp('Identifiant de session Stripe Checkout');

        yield TextField::new('stripePaymentIntentId', 'Payment Intent Stripe')
            ->hideOnIndex()
            ->setHelp('Identifiant de paiement Stripe');

        yield TextField::new('trackingNumber', 'Numéro de suivi')
            ->hideOnIndex();

        yield TextField::new('carrier', 'Transporteur')
            ->hideOnIndex();

        if ($pageName === Crud::PAGE_INDEX || $pageName === Crud::PAGE_DETAIL) {
            yield Field::new('shippedAt', 'Date d\'expédition')
                ->formatValue(function ($value) {
                    return $value instanceof \DateTimeInterface ? $value->format('d/m/Y H:i') : '-';
                })
                ->setTemplatePath('admin/field/date.html.twig')
                ->hideOnIndex();
        } else {
            yield TextField::new('shippedAt', 'Date d\'expédition')
                ->setFormType(\Symfony\Component\Form\Extension\Core\Type\DateTimeType::class)
                ->setFormTypeOptions([
                    'widget' => 'single_text',
                    'html5' => false,
                    'format' => 'dd/MM/yyyy HH:mm',
                    'required' => false,
                ])
                ->hideOnIndex();
        }
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices([
                'En attente' => 'pending',
                'Payée' => 'paid',
                'Confirmée' => 'confirmed',
                'Expédiée' => 'shipped',
                'Livrée' => 'delivered',
                'Annulée' => 'cancelled',
            ]))
            ->add('user', 'Utilisateur');
        // Filtre de date retiré car il nécessite l'extension PHP Intl
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE);
    }
}

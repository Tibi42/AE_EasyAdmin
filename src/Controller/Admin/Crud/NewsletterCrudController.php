<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Newsletter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

class NewsletterCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Newsletter::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Abonné')
            ->setEntityLabelInPlural('Newsletter')
            ->setPageTitle('index', 'Liste des abonnés')
            ->setPageTitle('new', 'Ajouter un abonné')
            ->setPageTitle('edit', 'Modifier l\'abonné')
            ->setDefaultSort(['subscribedAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm();
        
        yield EmailField::new('email', 'Email')
            ->setRequired(true);
        
        yield BooleanField::new('isActive', 'Actif')
            ->setHelp('Abonné actif ou désactivé');
        
        yield Field::new('subscribedAt', 'Date d\'inscription')
            ->formatValue(function ($value) {
                return $value instanceof \DateTimeInterface ? $value->format('d/m/Y H:i') : '-';
            })
            ->setTemplatePath('admin/field/date.html.twig')
            ->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('email')
            ->add(BooleanFilter::new('isActive', 'Actif'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}

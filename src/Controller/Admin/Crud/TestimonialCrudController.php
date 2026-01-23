<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Testimonial;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

class TestimonialCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Testimonial::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Témoignage')
            ->setEntityLabelInPlural('Témoignages')
            ->setPageTitle('index', 'Liste des témoignages')
            ->setPageTitle('new', 'Créer un témoignage')
            ->setPageTitle('edit', 'Modifier le témoignage')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm();
        
        yield TextField::new('name', 'Nom')
            ->setRequired(true);
        
        yield EmailField::new('email', 'Email')
            ->setRequired(true);
        
        yield TextareaField::new('content', 'Contenu')
            ->setRequired(true)
            ->hideOnIndex();
        
        yield IntegerField::new('rating', 'Note')
            ->setRequired(true)
            ->setHelp('Note sur 5')
            ->setFormTypeOption('attr', ['min' => 1, 'max' => 5]);
        
        yield BooleanField::new('isApproved', 'Approuvé')
            ->setHelp('Afficher ce témoignage sur le site');
        
        yield Field::new('createdAt', 'Date de création')
            ->formatValue(function ($value) {
                return $value instanceof \DateTimeInterface ? $value->format('d/m/Y H:i') : '-';
            })
            ->setTemplatePath('admin/field/date.html.twig')
            ->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('name')
            ->add('email')
            ->add(BooleanFilter::new('isApproved', 'Approuvé'))
            ->add('rating', 'Note');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}

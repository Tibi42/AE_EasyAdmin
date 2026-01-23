<?php

namespace App\Controller\Admin\Crud;

use App\Entity\OrderItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrderItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OrderItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article de commande')
            ->setEntityLabelInPlural('Articles de commande')
            ->setPageTitle('index', 'Liste des articles')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm();
        
        yield AssociationField::new('orderRef', 'Commande')
            ->setRequired(true);
        
        yield AssociationField::new('product', 'Produit')
            ->setRequired(true);
        
        yield TextField::new('productName', 'Nom du produit')
            ->setRequired(true)
            ->setHelp('Nom du produit au moment de la commande');
        
        yield IntegerField::new('quantity', 'Quantité')
            ->setRequired(true);
        
        yield MoneyField::new('price', 'Prix unitaire')
            ->setCurrency('EUR')
            ->setRequired(true);
        
        yield MoneyField::new('total', 'Total')
            ->setCurrency('EUR')
            ->setRequired(true);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::EDIT);
    }
}

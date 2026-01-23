<?php

namespace App\Form\Admin;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $categories = $options['categories'] ?? [];
        $categoryChoices = [];
        
        // Créer un tableau associatif pour les choix
        // Les catégories peuvent être des objets Category ou des chaînes
        foreach ($categories as $category) {
            if (is_object($category) && method_exists($category, 'getName')) {
                $categoryName = $category->getName();
            } else {
                $categoryName = (string) $category;
            }
            $categoryChoices[$categoryName] = $categoryName;
        }
        
        // Trier les choix par ordre alphabétique
        ksort($categoryChoices);
        
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix (€)',
                'scale' => 2,
                'attr' => [
                    'step' => '0.01',
                    'min' => '0',
                    'inputmode' => 'decimal',
                ],
            ])
            ->add('stock', NumberType::class, [
                'label' => 'Stock',
                'required' => false,
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => $categoryChoices,
                'placeholder' => 'Sélectionner une catégorie',
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Image (JPG, PNG, WEBP)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        mimeTypesMessage: 'Veuillez uploader une image valide (JPG, PNG, WEBP)'
                    )
                ],
            ])
            ->add('isFeatured', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'label' => 'Mettre en avant sur la page d\'accueil',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'label_attr' => [
                    'class' => 'form-check-label',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'categories' => [],
        ]);
    }
}

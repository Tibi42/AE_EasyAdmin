<?php

namespace App\Form;

use App\Entity\Testimonial;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class TestimonialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom complet',
                'attr' => ['placeholder' => 'Votre nom', 'class' => 'form-control'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre nom'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'votre@email.com', 'class' => 'form-control'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre email'),
                    new Email(message: 'Veuillez saisir un email valide'),
                ],
            ])
            ->add('rating', HiddenType::class, [
                'data' => 5,
                'attr' => ['data-rating-target' => 'input'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez donner une note'),
                    new Range(min: 1, max: 5, notInRangeMessage: 'La note doit être comprise entre {{ min }} et {{ max }}'),
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Commentaire',
                'attr' => [
                    'placeholder' => 'Votre avis sur nos produits...',
                    'class' => 'form-control',
                    'rows' => 4
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir un commentaire'),
                    new Length(min: 10, minMessage: 'Votre commentaire doit faire au moins {{ limit }} caractères'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Testimonial::class,
            'empty_data' => function () {
                $t = new Testimonial();
                $t->setRating(5);
                return $t;
            }
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'testimonial';
    }
}

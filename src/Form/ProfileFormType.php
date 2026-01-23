<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire de modification du profil utilisateur côté client
 */
class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'votre@email.com',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre email'),
                    new Email(message: 'Veuillez saisir un email valide'),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre prénom',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Length(
                        max: 255,
                        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre nom',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Length(
                        max: 255,
                        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'placeholder' => '06 12 34 56 78',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Length(
                        max: 20,
                        maxMessage: 'Le numéro de téléphone ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => [
                    'placeholder' => '123 Rue du Commerce',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Length(
                        max: 255,
                        maxMessage: 'L\'adresse ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => [
                    'placeholder' => '75000',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Length(
                        max: 10,
                        maxMessage: 'Le code postal ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Paris',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Length(
                        max: 255,
                        maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
            ])
            ->add('country', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'attr' => [
                    'placeholder' => 'France',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Length(
                        max: 255,
                        maxMessage: 'Le pays ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

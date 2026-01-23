<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom complet',
                'attr' => [
                    'placeholder' => 'Votre nom',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre nom'),
                    new Length(
                        min: 2,
                        minMessage: 'Votre nom doit contenir au moins {{ limit }} caractères',
                        max: 255
                    ),
                ],
            ])
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
            ->add('subject', TextType::class, [
                'label' => 'Sujet',
                'attr' => [
                    'placeholder' => 'Sujet de votre message',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir un sujet'),
                    new Length(
                        min: 3,
                        minMessage: 'Le sujet doit contenir au moins {{ limit }} caractères',
                        max: 255
                    ),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'placeholder' => 'Votre message...',
                    'class' => 'form-control',
                    'rows' => 6,
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir un message'),
                    new Length(
                        min: 10,
                        minMessage: 'Votre message doit contenir au moins {{ limit }} caractères',
                        max: 2000
                    ),
                ],
            ])
        ;
    }
    public function getBlockPrefix(): string
    {
        return 'contact_form';
    }
}

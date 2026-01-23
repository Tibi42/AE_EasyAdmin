<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire de changement de mot de passe depuis le profil utilisateur
 */
class ChangePasswordFormType extends AbstractType
{
    /**
     * Construit le formulaire avec vérification du mot de passe actuel et confirmation
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'mapped' => false,
                'attr' => [
                    'placeholder' => '••••••••',
                    'class' => 'form-control',
                    'autocomplete' => 'current-password',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre mot de passe actuel'),
                ],
            ])
            ->add('newPassword', PasswordType::class, [
                'label' => 'Nouveau mot de passe',
                'mapped' => false,
                'attr' => [
                    'placeholder' => '••••••••',
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir un nouveau mot de passe'),
                    new Length(
                        min: 6,
                        minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        max: 4096
                    ),
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'Confirmer le nouveau mot de passe',
                'mapped' => false,
                'attr' => [
                    'placeholder' => '••••••••',
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez confirmer votre nouveau mot de passe'),
                ],
            ]);

        // Validation personnalisée pour vérifier que les deux mots de passe correspondent
        $builder->addEventListener(
            \Symfony\Component\Form\FormEvents::POST_SUBMIT,
            function (\Symfony\Component\Form\FormEvent $event) {
                $form = $event->getForm();
                $newPassword = $form->get('newPassword')->getData();
                $confirmPassword = $form->get('confirmPassword')->getData();

                if ($newPassword !== $confirmPassword) {
                    $form->get('confirmPassword')->addError(
                        new \Symfony\Component\Form\FormError('Les deux mots de passe ne correspondent pas.')
                    );
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}

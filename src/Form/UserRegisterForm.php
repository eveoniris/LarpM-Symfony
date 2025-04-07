<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRegisterForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'Adresse email',
            'required' => true,
        ])
            ->add('email_contact', EmailType::class, [
                'label' => 'Adresse email de contact',
                'help' => "Si parent d'un mineur, ou que l'email de conneixon ne convient pas pour être contacté",
            ])
            ->add('username', TextType::class, [
                'label' => 'Nom ou pseudo',
                'required' => true,
            ])->add('pwd', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => true,
            ])
            ->add('confirm_password', PasswordType::class, [
                'label' => 'Confirmer le mot de passe',
                'required' => true,
                'mapped' => false,
            ])->add(
                'save',
                SubmitType::class,
                [
                    'label' => "S'enregistrer",
                    'attr' => [
                        'class' => 'btn btn-secondary',
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // ...
            'data_class' => User::class,
            'constraints' => [
                new UniqueEntity(fields: ['email']),
                new UniqueEntity(fields: ['username']),
            ],
        ]);
    }

    public function getName(): string
    {
        return 'userRegister';
    }
}

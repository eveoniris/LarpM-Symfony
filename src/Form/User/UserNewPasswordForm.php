<?php

namespace App\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class UserNewPasswordForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('password', TextType::class, [
            'label' => 'Nouveau mot de passe',
            'required' => true,
        ])
            ->add('confirm_password', TextType::class, [
                'label' => 'Confirmer le nouveau mot de passe',
                'required' => true,
            ]);
    }

    public function getName(): string
    {
        return 'NewPassword';
    }
}
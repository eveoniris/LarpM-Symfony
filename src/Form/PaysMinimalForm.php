<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PaysMinimalForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', 'text', [
            'required' => true, ])
            ->add('description', 'textarea', [
                'required' => false, ]);
    }

    public function getName(): string
    {
        return 'paysMinimalForm';
    }
}

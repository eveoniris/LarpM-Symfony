<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RegionForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', 'text', [
                'required' => true, ]
        )
            ->add('description', 'textarea', [
                    'required' => false, ]
            )
            ->add('pays', 'entity', [
                    'required' => true,
                    'class' => 'App\Entity\Pays',
                    'property' => 'nom', ]
            )
            ->add('dirigeant', 'text', [
                    'required' => false, ]
            )
            ->add('capitale', 'text', [
                    'required' => false, ]
            )
            ->add('protection', 'integer', [
                    'label' => 'Valeur de protection',
                    'required' => false, ]
            )
            ->add('puissance', 'integer', [
                    'label' => 'Valeur de puissance actuelle',
                    'required' => false, ]
            )
            ->add('puissanceMax', 'integer', [
                'label' => 'Valeur de puissance maximum',
                'required' => false,
            ])
            ->add('ressources', 'entity', [
                'required' => false,
                'multiple' => true,
                'class' => \App\Entity\Ressource::class,
                'property' => 'label',
            ]);
    }

    public function getName(): string
    {
        return 'regionForm';
    }
}

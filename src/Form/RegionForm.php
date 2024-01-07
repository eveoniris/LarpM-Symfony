<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RegionForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'required' => true, ]
        )
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                    'required' => false, ]
            )
            ->add('pays', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                    'required' => true,
                    'class' => 'App\Entity\Pays',
                    'property' => 'nom', ]
            )
            ->add('dirigeant', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                    'required' => false, ]
            )
            ->add('capitale', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                    'required' => false, ]
            )
            ->add('protection', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                    'label' => 'Valeur de protection',
                    'required' => false, ]
            )
            ->add('puissance', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                    'label' => 'Valeur de puissance actuelle',
                    'required' => false, ]
            )
            ->add('puissanceMax', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'label' => 'Valeur de puissance maximum',
                'required' => false,
            ])
            ->add('ressources', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
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

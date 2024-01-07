<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RegionMinimalForm extends AbstractType
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
            );
    }

    public function getName(): string
    {
        return 'regionMinimalForm';
    }
}

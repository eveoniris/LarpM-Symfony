<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RegionMinimalForm extends AbstractType
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
            );
    }

    public function getName(): string
    {
        return 'regionMinimalForm';
    }
}

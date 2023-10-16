<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class NiveauForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', 'text', [
                'required' => true, ]
        )
            ->add('niveau', 'integer', [
                    'required' => true, ]
            )
            ->add('cout_favori', 'integer', [
                    'label' => 'Coût favori',
                    'required' => true, ]
            )
            ->add('cout', 'integer', [
                    'label' => 'Coût normal',
                    'required' => true, ]
            )
            ->add('cout_meconu', 'integer', [
                    'label' => 'Coût méconnu',
                    'required' => true, ]
            );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data' => 'App\Entity\Niveau',
        ]);
    }

    public function getName(): string
    {
        return 'niveauForm';
    }
}

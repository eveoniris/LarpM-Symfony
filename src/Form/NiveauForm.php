<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NiveauForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'required' => true, ]
        )
            ->add('niveau', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                    'required' => true, ]
            )
            ->add('cout_favori', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                    'label' => 'Coût favori',
                    'required' => true, ]
            )
            ->add('cout', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                    'label' => 'Coût normal',
                    'required' => true, ]
            )
            ->add('cout_meconu', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                    'label' => 'Coût méconnu',
                    'required' => true, ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
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

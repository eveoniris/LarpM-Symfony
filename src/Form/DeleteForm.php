<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeleteForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'delete',
            SubmitType::class,
            ['label' => 'Supprimer', 'attr' => ['class' => 'btn btn-danger']]
        );

        if (!isset($options['class'])) {
            throw new InvalidConfigurationException('Missing entity class in options');
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => null, // must be set in the controller
            'cascade_validation' => true,
        ]);
    }

    public function getName(): string
    {
        return 'formEntityDelete';
    }
}

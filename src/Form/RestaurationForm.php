<?php

namespace App\Form;

use App\Entity\Restauration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
class RestaurationForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'required' => true,
            'label' => 'Label',
        ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                ],
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Restauration::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'restauration';
    }
}

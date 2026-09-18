<?php

declare(strict_types=1);

namespace App\Form\Cosmogonie;

use App\Entity\Cosmogonie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CosmogonieType extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => true,
                'label' => 'Titre',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                ],
            ])
            ->add('description_scenariste', TextareaType::class, [
                'required' => false,
                'label' => 'Information scénariste',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                ],
            ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cosmogonie::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }
}

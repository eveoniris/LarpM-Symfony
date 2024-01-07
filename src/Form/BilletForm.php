<?php

namespace App\Form;

use App\Entity\Billet;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BilletForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'label' => 'Label',
            'required' => true,
        ])
            ->add('gn', EntityType::class, [
                'label' => 'Concerne le GN',
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'class' => \App\Entity\Gn::class,
                //'property' => 'label',
            ])
            ->add('fedegn', ChoiceType::class, [
                'label' => 'A transmettre à la Fédégn',
                'required' => true,
                'choices' => [
                    true,
                    false,
                ],
                'choice_label' => static fn ($value) => match ($value) {
                    true => 'Oui',
                    false => 'Non',
                },
                'expanded' => true,
            ])
                ->add('description', TextareaType::class, [
                    'required' => true,
                    'label' => 'Description',
                    'attr' => [
                        'rows' => 9,
                  // TODO 'class' => 'tinymce',
                    ],
                ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Billet::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'billetDelete';
    }
}

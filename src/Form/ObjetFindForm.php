<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ObjetFindForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', TextType::class, [
            'required' => true,
            'label' => 'Recherche',
        ])
            ->add('type', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '*',
                    'id',
                    'nom',
                    'description',
                    'numero',
                ],
                'choice_label' => static fn ($value) => match ($value) {
                    '*' => 'Tout',
                    'nom' => 'Nom',
                    'description' => 'Description',
                    'numero' => 'Numero',
                    'id' => 'ID',
                },
                'label' => 'Type',
            ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'objetFind';
    }
}

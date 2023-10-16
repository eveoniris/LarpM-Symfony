<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\FindJoueurForm.
 *
 * @author kevin
 */
class FindPersonnageForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('search', 'text', [
            'required' => true,
        ])
            ->add('type', 'choice', [
                'required' => true,
                'choices' => [
                    'lastName' => 'Nom du joueur',
                    'firstName' => 'Prénom du joueur',
                    'numero' => 'Numéro',
                ],
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
        return 'findPersonnage';
    }
}

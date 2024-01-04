<?php


namespace App\Form\GroupeSecondaire;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\SecondaryGroupFindForm.
 *
 * @author kevin
 */
class SecondaryGroupFindForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', 'text', [
            'required' => true,
            'label' => 'Valeur',
        ])
            ->add('type', 'choice', [
                'required' => true,
                'label' => 'Type',
                'choices' => [
                    'id' => 'Numéro',
                    'nom' => 'Nom du groupe secondaire',
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
        return 'secondaryGroupFind';
    }
}

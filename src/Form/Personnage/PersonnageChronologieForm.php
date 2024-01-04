<?php


namespace App\Form\Personnage;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageChronologieForm.
 *
 * @author Kevin F.
 */
class PersonnageChronologieForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('annee', 'integer', [
            'required' => true,
            'label' => 'Année de l\'évènement.',
            'mapped' => false,
        ])
            ->add('evenement', 'text', [
                'required' => true,
                'trim' => true,
                'mapped' => false,
                'label' => 'Décrivez l\'évènement',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageChronologie';
    }
}

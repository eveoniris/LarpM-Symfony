<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * LarpManager\Form\PersonnageXpForm.
 *
 * @author kevin
 */
class PersonnageXpForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('xp', 'integer', [
            'label' => 'Expérience à ajouter',
            'required' => true,
        ])
            ->add('explanation', 'text', [
                'label' => 'Explication',
                'required' => true,
            ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageXp';
    }
}

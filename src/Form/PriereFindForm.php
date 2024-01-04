<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\PriereFindForm.
 *
 * @author Gectou4
 */
class PriereFindForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', 'text', [
            'required' => true,
            'label' => 'Recherche',
        ])
            ->add('type', 'choice', [
                'required' => true,
                'choices' => [
                    'label' => 'Nom',
                    'description' => 'Description',
                    'annonce' => 'Annonce',
                    'sphere' => 'Sphere',
                    'id' => 'ID',
                ],
                'label' => 'Type',
            ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'priereFind';
    }
}

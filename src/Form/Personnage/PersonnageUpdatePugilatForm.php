<?php


namespace App\Form\Personnage;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageUpdatePugilatForm.
 *
 * @author Kevin F.
 */
class PersonnageUpdatePugilatForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('pugilat', 'integer', [
            'required' => true,
            'label' => 'Combien de points de Pugilat voulez-vous ajouter ? (indiquez une valeur négative pour retirer des points)',
            'mapped' => false,
            'attr' => ['max' => 6],
        ])
            ->add('explication', 'textarea', [
                'required' => true,
                'mapped' => false,
                'label' => 'Donnez une explication',
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
        return 'personnageUpdatePugilat';
    }
}

<?php


namespace App\Form\Personnage;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageUpdateRenommeForm.
 *
 * @author kevin
 */
class PersonnageUpdateRenommeForm extends AbstractType
{
    /**
     * Construction du formulaire
     * Seul les éléments ne dépendant pas des points d'expérience sont modifiables.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('renomme', 'integer', [
            'required' => true,
            'label' => 'Combien de points de Renommée voulez-vous ajouter ? (indiquez une valeur négative pour retirer des points)',
            'mapped' => false,
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
        return 'personnageUpdateRenomme';
    }
}

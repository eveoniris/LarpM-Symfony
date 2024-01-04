<?php


namespace App\Form\GroupeSecondaire;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Groupe\GroupeEnvelopeForm.
 *
 * @author kevin
 */
class GroupeSecondaireMaterielForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('materiel', 'textarea', [
            'label' => "Contenu libre de l'enveloppe",
            'required' => false,
            'attr' => [
                'row' => 9,
            ],
        ])
            ->add('save', 'submit', ['label' => 'Sauvegarder']);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\SecondaryGroup::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeSecondaireMateriel';
    }
}

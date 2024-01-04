<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\JoueurXpForm.
 *
 * @author kevin
 */
class JoueurXpForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', 'text', [
            'label' => 'Nom civil',
            'required' => true,
        ])
            ->add('prenom', 'text', [
                'label' => 'Prénom civil',
                'required' => true,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => 'App\Entity\Joueur',
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'joueurXp';
    }
}

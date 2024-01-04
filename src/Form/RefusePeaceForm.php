<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\RefusePeaceForm.
 *
 * @author kevin
 */
class RefusePeaceForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => 'App\Entity\GroupeEnnemi',
        ]);
    }

    /**
     * Nom du formlaire.
     */
    public function getName(): string
    {
        return 'refusePeace';
    }
}

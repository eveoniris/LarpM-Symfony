<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\RequestPeaceForm.
 *
 * @author kevin
 */
class RequestPeaceForm extends AbstractType
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
    public function configureOptions(OptionsResolver $resolver): void
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
        return 'requestPeace';
    }
}

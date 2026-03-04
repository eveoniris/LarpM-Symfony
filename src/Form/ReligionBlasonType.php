<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Religion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\TrombineForm.
 *
 * @author kevin
 */
class ReligionBlasonType extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('blason', 'file', [
            'label' => 'Choisissez votre fichier',
            'required' => true,
            'mapped' => false,
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Religion::class,
        ]);
    }

    /*
     * Nom du formulaire.
     */
}

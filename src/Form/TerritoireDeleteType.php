<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Territoire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\TerritoireDeleteForm.
 *
 * @author kevin
 */
class TerritoireDeleteType extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Territoire::class,
        ]);
    }

    /*
     * Nom du formulaire.
     */
}

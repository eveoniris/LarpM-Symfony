<?php

declare(strict_types=1);

namespace App\Form\Item;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Monnaie\ItemDeleteForm.
 *
 * @author kevin
 */
class ItemDeleteType extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, [
            'label' => 'Supprimer',
            'attr' => [
                'class' => 'btn btn-danger',
            ],
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Item::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
}

<?php

declare(strict_types=1);

namespace App\Form\Groupe;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\GroupeSecondaireTypeForm.
 *
 * @author kevin
 */
class GroupeSecondaireTypeType extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => true,
        ])->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
            'required' => false,
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data' => \App\Entity\SecondaryGroupType::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
}

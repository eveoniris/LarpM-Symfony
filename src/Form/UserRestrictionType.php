<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\UserRestrictionForm.
 *
 * @author kevin
 */
class UserRestrictionType extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('restrictions', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'required' => false,
            'label' => 'Choisissez vos restrictions alimentaires dans la liste ci-dessus',
            'multiple' => true,
            'expanded' => true,
            'class' => \App\Entity\Restriction::class,
            'choice_label' => 'label',
            'placeholder' => 'Aucune',
            'empty_data' => null,
        ])->add('new_restriction', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => false,
            'label' => 'Si votre restriction alimentaire n\'apparait pas dans la liste, indiquez la ici',
            'mapped' => false,
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
}

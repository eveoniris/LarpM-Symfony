<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\UserRestrictionForm.
 *
 * @author kevin
 */
class UserRestrictionForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('restrictions', 'entity', [
            'required' => false,
            'label' => 'Choisissez vos restrictions alimentaires dans la liste ci-dessus',
            'multiple' => true,
            'expanded' => true,
            'class' => \App\Entity\Restriction::class,
            'property' => 'label',
            'placeholder' => 'Aucune',
            'empty_data' => null,
        ])
            ->add('new_restriction', 'text', [
                'required' => false,
                'label' => 'Si votre restriction alimentaire n\'apparait pas dans la liste, indiquez la içi',
                'mapped' => false,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\User::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'UserRestriction';
    }
}

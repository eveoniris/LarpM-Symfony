<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\AgeForm.
 *
 * @author kevin
 */
class AgeForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', 'text', [
            'required' => true,
        ])
            ->add('description', 'textarea', [
                'required' => false,
            ])
            ->add('enableCreation', 'choice', [
                'required' => true,
                'choices' => [true => 'Oui', false => 'Non'],
                'label' => 'Disponible lors de la création d\'un personnage',
            ])
            ->add('bonus', 'integer', [
                'label' => 'XP en bonus',
                'required' => true,
            ])
            ->add('minimumValue', 'integer', [
                'label' => 'Age de départ',
                'required' => true,
            ]);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Age::class,
        ]);
    }

    /**
     * Nom du formlaire.
     */
    public function getName(): string
    {
        return 'age';
    }
}

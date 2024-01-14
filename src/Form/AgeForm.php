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
        $builder->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => true,
        ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => false,
            ])
            ->add('enableCreation', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'choices' => ['Oui' => true, false => 'Non'],
                'label' => 'Disponible lors de la création d\'un personnage',
            ])
            ->add('bonus', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'label' => 'XP en bonus',
                'required' => true,
            ])
            ->add('minimumValue', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
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

<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\ClasseForm.
 *
 * @author kevin
 */
class ConstructionForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', 'text', [
            'label' => 'Le nom de la construction',
            'required' => true,
        ])
            ->add('description', 'textarea', [
                'label' => 'La description de la construction',
                'required' => true,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 9,
                ],
            ])
            ->add('defense', 'integer', [
                'label' => 'La valeur de défense de la construction',
                'required' => true,
            ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Construction::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'construction';
    }
}

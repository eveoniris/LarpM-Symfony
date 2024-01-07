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
        $builder->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'label' => 'Le nom de la construction',
            'required' => true,
        ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'La description de la construction',
                'required' => true,
                'attr' => [
                    // TODO 'class' => 'tinymce',
                    'rows' => 9,
                ],
            ])
            ->add('defense', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
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

<?php


namespace App\Form;

use App\Entity\Ingredient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\IngredientForm.
 *
 * @author kevin
 */
class IngredientForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => true,
            'label' => 'Label',
        ])
            ->add('niveau', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'choices' => ['1' => 1, '2' => 2, '3' => 3, '4' => 4],
                'label' => 'Niveau',
            ])
            ->add('dose', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'required' => true,
                'label' => 'Dose',
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 9],
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ingredient::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'ingredient';
    }
}

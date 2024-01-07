<?php


namespace App\Form\Territoire;

use LarpManager\Repository\IngredientRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\TerritoireIngredientsForm.
 *
 * @author kevin
 */
class TerritoireIngredientsForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('ingredients', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'required' => false,
            'label' => 'Ingrédients',
            'class' => \App\Entity\Ingredient::class,
            'multiple' => true,
            'expanded' => true,
            'mapped' => true,
            'property' => 'label',
            'query_builder' => static function (IngredientRepository $er) {
                return $er->createQueryBuilder('i')->orderBy('i.label', 'ASC')->groupby('i.label');
            },
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Territoire::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'territoireIngredients';
    }
}

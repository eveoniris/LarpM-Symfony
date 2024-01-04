<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\GroupeHasIngredientType.
 *
 * @author kevin
 */
class GroupeHasIngredientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantite', 'integer', [
                'label' => 'quantite',
                'required' => true,
            ])
            ->add('ingredient', 'entity', [
                'label' => "Choisissez l'ingredient",
                'required' => true,
                'class' => \App\Entity\Ingredient::class,
                'property' => 'fullLabel',
                'query_builder' => static function (\LarpManager\Repository\IngredientRepository $er) {
                    $qb = $er->createQueryBuilder('c');
                    $qb->orderBy('c.label', 'ASC')->addOrderBy('c.niveau', 'ASC');

                    return $qb;
                },
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\GroupeHasIngredient::class,
        ]);
    }

    public function getName(): string
    {
        return 'groupeHasIngredient';
    }
}

<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('quantite', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'label' => 'quantite',
                'required' => true,
            ])
            ->add('ingredient', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => "Choisissez l'ingredient",
                'required' => true,
                'class' => \App\Entity\Ingredient::class,
                'choice_label' => 'fullLabel',
                'query_builder' => static function (\LarpManager\Repository\IngredientRepository $er) {
                    $qb = $er->createQueryBuilder('c');
                    $qb->orderBy('c.label', 'ASC')->addOrderBy('c.niveau', 'ASC');

                    return $qb;
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
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

<?php


namespace App\Form\Type;

use App\Entity\GroupeHasIngredient;
use App\Entity\Ingredient;
use App\Repository\IngredientRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
            ->add('quantite', IntegerType::class, [
                'label' => 'quantite',
                'required' => true,
            ])
            ->add('ingredient', EntityType::class, [
                'label' => "Choisissez l'ingredient",
                'required' => true,
                'class' => Ingredient::class,
                'choice_label' => 'fullLabel',
                'query_builder' => static function (IngredientRepository $er) {
                    $qb = $er->createQueryBuilder('c');
                    $qb->orderBy('c.label', 'ASC')->addOrderBy('c.niveau', 'ASC');

                    return $qb;
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.GroupeHasIngredient::class,
        ]);
    }

    public function getName(): string
    {
        return 'groupeHasIngredient';
    }
}

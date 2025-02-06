<?php


namespace App\Form\Type;

use App\Entity\Ingredient;
use App\Entity\PersonnageIngredient;
use App\Repository\IngredientRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnageIngredientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', IntegerType::class, [
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
            'data_class' => PersonnageIngredient::class,
        ]);
    }

    public function getName(): string
    {
        return 'personnageIngredient';
    }
}

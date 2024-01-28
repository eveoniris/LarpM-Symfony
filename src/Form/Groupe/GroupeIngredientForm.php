<?php


namespace App\Form\Groupe;

use App\Form\Type\GroupeHasIngredientType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\GroupeIngredientForm.
 *
 * @author kevin
 */
class GroupeIngredientForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('groupeHasIngredients', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
            'label' => 'Ingredients',
            'required' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'entry_type' => GroupeHasIngredientType::class,
        ])
            ->add('random', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'mapped' => false,
                'label' => 'X ingrédients choisis au hasard',
                'required' => false,
                'attr' => [
                    'help' => 'Indiquez combien d\'ingrédient il faut ajouter à ce groupe.',
                ],
            ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Groupe::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeIngredient';
    }
}

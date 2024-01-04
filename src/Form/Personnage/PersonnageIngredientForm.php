<?php


namespace App\Form\Personnage;

use App\Form\Type\PersonnageIngredientType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\PersonnageIngredientForm.
 *
 * @author kevin
 */
class PersonnageIngredientForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnageIngredients', 'collection', [
            'label' => 'Ingredients',
            'required' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'type' => new PersonnageIngredientType(),
        ])
            ->add('random', 'integer', [
                'mapped' => false,
                'label' => 'X ingrédients choisis au hasard',
                'required' => false,
                'attr' => [
                    'help' => 'Indiquez combien d\'ingrédient il faut ajouter à ce personnage.',
                ],
            ])
            ->add('valider', 'submit', ['label' => 'Valider']);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Personnage::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageIngredient';
    }
}

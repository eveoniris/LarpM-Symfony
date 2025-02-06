<?php


namespace App\Form\Personnage;

use App\Entity\Personnage;
use App\Form\Type\PersonnageIngredientType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnageIngredientForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnageIngredients', CollectionType::class, [
            'label' => 'Ingredients',
            'required' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'entry_type' => PersonnageIngredientType::class,
        ])
            ->add('random', IntegerType::class, [
                'mapped' => false,
                'label' => 'X ingrédients choisis au hasard',
                'required' => false,
                'attr' => [
                    'help' => 'Indiquez combien d\'ingrédient il faut ajouter à ce personnage.',
                ],
            ])
            ->add('valider', SubmitType::class, ['label' => 'Valider']);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personnage::class,
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

<?php


namespace App\Form\Territoire;

use App\Repository\ConstructionRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\TerritoireIngredientsForm.
 *
 * @author kevin
 */
class TerritoireConstructionForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('constructions', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'required' => false,
            'label' => 'Ajouter une construction',
            'class' => \App\Entity\Construction::class,
            'multiple' => true,
            'expanded' => true,
            //'mapped' => true,
            'choice_label' => 'label',
            'query_builder' => static function (ConstructionRepository $er) {
                return $er->createQueryBuilder('c')->orderBy('c.label', 'ASC');
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
        return 'constructionIngredients';
    }
}

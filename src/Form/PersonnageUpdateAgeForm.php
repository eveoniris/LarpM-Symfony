<?php


namespace App\Form;

use App\Entity\Age;
use App\Entity\Personnage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnageUpdateAgeForm extends AbstractType
{
    /**
     * Construction du formulaire
     * Seul les éléments ne dépendant pas des points d'expérience sont modifiables.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('age', EntityType::class, [
            'required' => true,
            'multiple' => false,
            'expanded' => true,
            'class' => Age::class,
            'choice_label' => 'fullLabel',
            'label' => 'Choisissez la catégorie d\'age du personnage',
        ])
            ->add('ageReel', NumberType::class, [
                'required' => true,
                'label' => "Indiquez l'age du personnage",
            ]);
    }

    /**
     * Définition de l'entité concerné.
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
        return 'personnageUpdateAge';
    }
}

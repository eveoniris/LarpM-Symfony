<?php


namespace App\Form\Personnage;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageUpdateHeroismeForm.
 *
 * @author kevin
 */
class PersonnageUpdateHeroismeForm extends AbstractType
{
    /**
     * Construction du formulaire
     * Seul les éléments ne dépendant pas des points d'expérience sont modifiables.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('heroisme', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
            'required' => true,
            'label' => 'Combien de points d\'Héroïsme voulez-vous ajouter ? (indiquez une valeur négative pour retirer des points)',
            'mapped' => false,
            'attr' => ['max' => 3],
        ])
            ->add('explication', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => true,
                'mapped' => false,
                'label' => 'Donnez une explication',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageUpdateHeroisme';
    }
}

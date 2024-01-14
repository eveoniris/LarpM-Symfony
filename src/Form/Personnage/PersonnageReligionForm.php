<?php


namespace App\Form\Personnage;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Personnage\PersonnageReligionForm.
 *
 * @author kevin
 */
class PersonnageReligionForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('religionLevel', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'required' => true,
            'label' => 'Votre degré de fanatisme',
            'class' => \App\Entity\ReligionLevel::class,
            'choice_label' => 'label',
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\PersonnagesReligions::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageReligion';
    }
}

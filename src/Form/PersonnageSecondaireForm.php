<?php


namespace App\Form;

use App\Form\Type\PersonnageSecondairesCompetencesType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageSecondaireForm.
 *
 * @author kevin
 */
class PersonnageSecondaireForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'classe', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'required' => true,
            'label' => 'Choisissez la classe',
            'class' => \App\Entity\Classe::class,
            'choice_label' => 'label',
        ])
            ->add(
                'personnageSecondaireCompetences', 'collection', [
                'label' => 'Competences',
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'type' => new PersonnageSecondairesCompetencesType(),
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\PersonnageSecondaire::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageSecondaire';
    }
}

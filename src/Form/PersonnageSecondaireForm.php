<?php

namespace App\Form;

use App\Entity\Classe;
use App\Entity\PersonnageSecondaire;
use App\Form\Type\PersonnageSecondairesCompetencesType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnageSecondaireForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'classe', EntityType::class, [
                'required' => true,
                'label' => 'Choisissez la classe',
                'class' => Classe::class,
                'choice_label' => 'label',
            ])
            ->add(
                'personnageSecondaireCompetences', CollectionType::class, [
                    'label' => 'Competences',
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'entry_type' => PersonnageSecondairesCompetencesType::class,
                ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PersonnageSecondaire::class,
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

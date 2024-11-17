<?php

namespace App\Form\Personnage;

use App\Entity\Lignee;
use App\Entity\Personnage;
use App\Repository\LigneeRepository;
use App\Repository\PersonnageRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnageLigneeForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('parent1', EntityType::class, [
            'label' => 'Choisissez le Parent Male ou Ambigüe du personnage',
            'expanded' => false,
            'required' => false,
            'autocomplete' => true,
            'class' => Personnage::class,
            'choice_label' => static fn (Personnage $personnage) => $personnage->getLigneeIdentity(),
            'query_builder' => static fn (PersonnageRepository $pr) => $pr->getFindGenderQueryBuilder(1, 3), // Men or ambigous
        ])
            ->add('parent2', EntityType::class, [
                'label' => 'Choisissez le Parent Femelle ou Ambigüe du personnage',
                'expanded' => false,
                'required' => false,
                'autocomplete' => true,
                'empty_data' => null,
                'class' => Personnage::class,
                'choice_label' => static fn (Personnage $personnage) => $personnage->getLigneeIdentity(),
                'query_builder' => static fn (PersonnageRepository $pr) => $pr->getFindGenderQueryBuilder(2, 3), // women or ambigous
            ])
            ->add('lignee', EntityType::class, [
                'label' => 'Choisissez la lignée de votre personnage ',
                'expanded' => false,
                'required' => false,
                'autocomplete' => true,
                'empty_data' => null,
                'class' => Lignee::class,
                'query_builder' => static fn (LigneeRepository $pr) => $pr->createQueryBuilder('p')->orderBy('p.nom', 'ASC'),
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
        return 'personnageLignee';
    }
}

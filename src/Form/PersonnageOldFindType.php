<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Personnage;
use App\Repository\PersonnageRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnageOldFindType extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnage', EntityType::class, [
            'label' => 'Choisissez le personnage',
            'choice_label' => 'nom',
            'autocomplete' => true,
            'class' => Personnage::class,
            'query_builder' => static fn (PersonnageRepository $personnageRepository) => $personnageRepository
                ->createQueryBuilder('p')
                ->leftJoin('p.participants', 'part')
                ->where('p.vivant = :vivant')
                ->andWhere('p.user = :uid OR part.user = :uid')
                ->setParameter('vivant', true)
                ->setParameter('uid', $builder->getData()->getUser()->getId())
                ->distinct()
                ->orderBy('p.nom', 'ASC'),
        ])->add('save', SubmitType::class, ['label' => 'Valider']);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Personnage::class,
        ]);
    }

    /*
     * Nom du formulaire.
     */
}

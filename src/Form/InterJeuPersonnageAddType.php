<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Personnage;
use App\Enum\InterJeuParticipantStatut;
use App\Repository\PersonnageRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class InterJeuPersonnageAddType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnage', EntityType::class, [
            'label' => 'Personnage',
            'required' => true,
            'class' => Personnage::class,
            'autocomplete' => true,
            'choice_label' => static fn (Personnage $p) => sprintf('#%d %s', $p->getId(), $p->getNom()),
            'query_builder' => static fn (PersonnageRepository $r) => $r->createQueryBuilder('p')->orderBy('p.nom', 'ASC'),
            'placeholder' => 'Rechercher par nom ou id…',
        ])->add('statut', EnumType::class, [
            'label' => 'Statut',
            'class' => InterJeuParticipantStatut::class,
            'choice_label' => static fn (InterJeuParticipantStatut $statut): string => $statut->getLabel(),
            'data' => InterJeuParticipantStatut::INSCRIT,
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}

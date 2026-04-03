<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Personnage;
use App\Repository\PersonnageRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InterJeuPersonnageAddType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnage', EntityType::class, [
            'label' => 'Personnage',
            'required' => true,
            'class' => Personnage::class,
            'autocomplete' => true,
            'choice_label' => fn (Personnage $p) => sprintf('#%d %s', $p->getId(), $p->getNom()),
            'query_builder' => static fn (PersonnageRepository $r) => $r->createQueryBuilder('p')->orderBy('p.nom', 'ASC'),
            'placeholder' => 'Rechercher par nom ou id…',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}

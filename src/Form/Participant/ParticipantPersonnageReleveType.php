<?php

declare(strict_types=1);

namespace App\Form\Participant;

use App\Entity\Participant;
use App\Entity\Personnage;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantPersonnageReleveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnageReleve', EntityType::class, [
            'required' => false,
            'label' => 'Choisissez votre personnage de relève.',
            'multiple' => false,
            'expanded' => true,
            'class' => Personnage::class,
            'choice_label' => 'identity',
            'placeholder' => 'Aucun',
            'empty_data' => null,
            'query_builder' => static fn (EntityRepository $er) => $er
                ->createQueryBuilder('p')
                ->join('p.user', 'u')
                ->where('u.id = :userId AND p.id <> :personnageId')
                ->setParameter('userId', $options['user_id'])
                ->setParameter('personnageId', $options['personnage_id']),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
            'user_id' => null,
            'personnage_id' => 0,
        ]);
    }
}

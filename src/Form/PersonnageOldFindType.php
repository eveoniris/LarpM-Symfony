<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Participant;
use App\Entity\Personnage;
use App\Repository\ParticipantRepository;
use App\Repository\PersonnageRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Participant>
 */
class PersonnageOldFindType extends AbstractType
{
    public function __construct(
        private readonly ParticipantRepository $participantRepository,
    ) {
    }

    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Participant $participant */
        $participant = $builder->getData();

        // Un personnage ne peut tenir qu'un seul rôle sur un GN : on écarte ceux
        // déjà engagés par une autre participation à ce GN.
        $exclus = $this->participantRepository->findPersonnageIdsEngagesSurGn($participant->getGn(), $participant);

        $builder->add('personnage', EntityType::class, [
            'label' => 'Choisissez le personnage',
            'choice_label' => 'nom',
            'autocomplete' => true,
            'class' => Personnage::class,
            'query_builder' => static function (PersonnageRepository $personnageRepository) use ($participant, $exclus) {
                $queryBuilder = $personnageRepository
                    ->createQueryBuilder('p')
                    ->leftJoin('p.participants', 'part')
                    ->where('p.vivant = :vivant')
                    ->andWhere('p.user = :uid OR part.user = :uid')
                    ->setParameter('vivant', true)
                    ->setParameter('uid', $participant->getUser()?->getId())
                    ->distinct()
                    ->orderBy('p.nom', 'ASC');

                if ([] !== $exclus) {
                    $queryBuilder->andWhere('p.id NOT IN (:exclus)')->setParameter('exclus', $exclus);
                }

                return $queryBuilder;
            },
        ])->add('save', SubmitType::class, ['label' => 'Valider']);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}

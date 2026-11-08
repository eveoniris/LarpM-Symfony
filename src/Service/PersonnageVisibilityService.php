<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Membre;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\User;
use App\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Qui peut voir la photo (trombine) d'un personnage.
 *
 * Utilisateur connecté obligatoire, puis : propriétaire, staff, personnage de renommée affiché sur
 * la page publique, ou membre (actuel ou ancien) d'un groupe en commun (principal ou transverse).
 * Les requêtes sont des COUNT indexés et s'arrêtent dès qu'une règle accepte.
 */
final readonly class PersonnageVisibilityService
{
    /** Renommée au-dessus de laquelle un personnage figure sur la page de renommée visible de tous les utilisateurs. */
    public const int RENOMME_PUBLIQUE = 10;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    public function canViewTrombine(Personnage $personnage): bool
    {
        $viewer = $this->security->getUser();
        if (!$viewer instanceof User) {
            return false;
        }

        if ($personnage->getUser() === $viewer || $this->isStaff()) {
            return true;
        }

        if ($personnage->getRenomme() > self::RENOMME_PUBLIQUE) {
            return true;
        }

        return $this->shareMainGroup($viewer, $personnage) || $this->shareSecondaryGroup($viewer, $personnage);
    }

    private function isStaff(): bool
    {
        foreach ([Role::SCENARISTE, Role::ORGA, Role::ADMIN] as $role) {
            if ($this->security->isGranted($role->value)) {
                return true;
            }
        }

        return false;
    }

    /** Groupe principal : participation (tous GN confondus) ou rattachement direct du personnage. */
    private function shareMainGroup(User $viewer, Personnage $personnage): bool
    {
        $parGn = (int) $this->entityManager
            ->createQuery(
                'SELECT COUNT(vp.id) FROM '
                . Participant::class
                . ' vp JOIN vp.groupeGn vgg, '
                . Participant::class
                . ' tp JOIN tp.groupeGn tgg '
                . 'WHERE vp.user = :viewer AND tp.personnage = :cible AND IDENTITY(vgg.groupe) = IDENTITY(tgg.groupe)',
            )
            ->setParameter('viewer', $viewer)
            ->setParameter('cible', $personnage)
            ->getSingleScalarResult();
        if ($parGn > 0) {
            return true;
        }

        if (null === $personnage->getGroupe()) {
            return false;
        }

        return (
            (int) $this->entityManager
                ->createQuery('SELECT COUNT(p.id) FROM ' . Personnage::class . ' p WHERE p.user = :viewer AND p.groupe = :groupe')
                ->setParameter('viewer', $viewer)
                ->setParameter('groupe', $personnage->getGroupe())
                ->getSingleScalarResult() > 0
        );
    }

    /** Groupe transverse : un membre non secret en commun. */
    private function shareSecondaryGroup(User $viewer, Personnage $personnage): bool
    {
        return (
            (int) $this->entityManager
                ->createQuery(
                    'SELECT COUNT(vm.id) FROM '
                    . Membre::class
                    . ' vm JOIN vm.personnage vpers, '
                    . Membre::class
                    . ' tm '
                    . 'WHERE vpers.user = :viewer AND tm.personnage = :cible AND tm.secret = false '
                    . 'AND IDENTITY(vm.secondaryGroup) = IDENTITY(tm.secondaryGroup)',
                )
                ->setParameter('viewer', $viewer)
                ->setParameter('cible', $personnage)
                ->getSingleScalarResult() > 0
        );
    }
}

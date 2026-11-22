<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Conception;
use App\Entity\Gn;
use App\Entity\Personnage;
use App\Entity\PersonnageLignee;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Héritiers d'un personnage mort : enfants issus d'une conception en jeu, ou liés par la lignée.
 *
 * Le nombre de GN principaux écoulés depuis la conception sert seulement à vérifier que l'âge de l'héritier permet de
 * l'interpréter (3 GN espacés d'environ 5 ans) : c'est un avertissement que le scénariste peut forcer, pas un blocage.
 */
final readonly class XpReportHeritiers
{
    public const int GN_MINIMUM = 3;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<array{personnage: Personnage, conception: ?Conception, gnEcoules: ?int, ageOk: bool}>
     */
    public function heritiersDe(Personnage $parent, ?DateTimeInterface $maintenant = null): array
    {
        $maintenant ??= new DateTimeImmutable();
        $lignes = [];

        // Enfants issus d'une conception réelle : la date de conception est connue.
        /** @var list<Conception> $conceptions */
        $conceptions = $this->entityManager
            ->createQueryBuilder()
            ->select('c')
            ->from(Conception::class, 'c')
            ->where('(c.parent1 = :parent OR c.parent2 = :parent)')
            ->andWhere('c.simulation = false')
            ->orderBy('c.id', 'ASC')
            ->setParameter('parent', $parent)
            ->getQuery()
            ->getResult();
        foreach ($conceptions as $conception) {
            foreach ($conception->getHeritiers() as $id) {
                $enfant = $this->entityManager->find(Personnage::class, $id);
                if (null === $enfant || array_key_exists($enfant->getId(), $lignes)) {
                    continue;
                }
                $ecoules = $this->gnEcoules($conception->getGn(), $maintenant);
                $lignes[$enfant->getId()] = [
                    'personnage' => $enfant,
                    'conception' => $conception,
                    'gnEcoules' => $ecoules,
                    'ageOk' => $ecoules >= self::GN_MINIMUM,
                ];
            }
        }

        // Enfants liés par la lignée sans conception connue : date de conception inconnue, à confirmer par le scénariste.
        /** @var list<PersonnageLignee> $liens */
        $liens = $this->entityManager
            ->createQueryBuilder()
            ->select('l')
            ->from(PersonnageLignee::class, 'l')
            ->where('l.parent1 = :parent OR l.parent2 = :parent')
            ->setParameter('parent', $parent)
            ->getQuery()
            ->getResult();
        foreach ($liens as $lien) {
            $enfant = $lien->getPersonnage();
            if (null === $enfant || array_key_exists($enfant->getId(), $lignes)) {
                continue;
            }
            $lignes[$enfant->getId()] = ['personnage' => $enfant, 'conception' => null, 'gnEcoules' => null, 'ageOk' => false];
        }

        return array_values($lignes);
    }

    /** GN dont la fin est passée et qui ont commencé après la fin du GN de la conception. */
    public function gnEcoules(Gn $gnConception, ?DateTimeInterface $maintenant = null): int
    {
        $maintenant ??= new DateTimeImmutable();
        $reference = $gnConception->getDateFin() ?? $gnConception->getDateDebut();
        if (null === $reference) {
            return 0;
        }

        return (int) $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(g.id)')
            ->from(Gn::class, 'g')
            ->where('g.date_debut > :reference')
            ->andWhere('g.date_fin IS NOT NULL AND g.date_fin <= :maintenant')
            ->setParameter('reference', $reference)
            ->setParameter('maintenant', $maintenant)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

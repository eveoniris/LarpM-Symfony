<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RulebookRegle;
use App\Enum\RulebookStatut;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RulebookRegle>
 */
class RulebookRegleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RulebookRegle::class);
    }

    /**
     * Règles consultables, dans l'ordre du rulebook (domaine puis position).
     *
     * - Équipe règle : toutes les règles, avec recherche aussi dans le détail technique et filtre par statut.
     * - Scénariste : seulement les règles publiées, de statut public, avec une version claire rédigée ; recherche limitée
     *   au code, au titre et à la version claire ; le filtre par statut est ignoré.
     *
     * @return list<RulebookRegle>
     */
    public function findConsultables(bool $equipeRegle, ?string $recherche = null, ?RulebookStatut $statut = null, ?string $domaineCode = null): array
    {
        $qb = $this
            ->createQueryBuilder('r')
            ->addSelect('d')
            ->innerJoin('r.domaine', 'd')
            ->orderBy('d.position', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->addOrderBy('r.position', 'ASC')
            ->addOrderBy('r.id', 'ASC');

        if (!$equipeRegle) {
            $publics = array_values(array_filter(RulebookStatut::cases(), static fn (RulebookStatut $s): bool => $s->isPublic()));
            $qb
                ->andWhere('r.publiee = true')
                ->andWhere('r.statut IN (:publics)')
                ->andWhere('r.texteClair IS NOT NULL')
                ->andWhere("TRIM(r.texteClair) <> ''")
                ->setParameter('publics', $publics);
        } elseif (null !== $statut) {
            $qb->andWhere('r.statut = :statut')->setParameter('statut', $statut);
        }

        if (null !== $domaineCode && '' !== $domaineCode) {
            $qb->andWhere('d.code = :domaine')->setParameter('domaine', $domaineCode);
        }

        $mots = preg_split('/\s+/', mb_strtolower(trim((string) $recherche)), -1, PREG_SPLIT_NO_EMPTY);
        foreach (false === $mots ? [] : $mots as $index => $mot) {
            $champs = ['LOWER(r.titre) LIKE :mot' . $index, 'LOWER(r.code) LIKE :mot' . $index, 'LOWER(r.texteClair) LIKE :mot' . $index];
            if ($equipeRegle) {
                $champs[] = 'LOWER(r.detailTechnique) LIKE :mot' . $index;
            }

            $qb->andWhere($qb->expr()->orX(...$champs))->setParameter('mot' . $index, '%' . addcslashes($mot, '%_\\') . '%');
        }

        /** @var list<RulebookRegle> */
        return $qb->getQuery()->getResult();
    }
}

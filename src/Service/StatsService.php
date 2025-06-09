<?php

namespace App\Service;

use App\Entity\Gn;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query\ResultSetMapping;

class StatsService
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getCompetenceGn(Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('total', 'total', 'integer');
        $rsm->addScalarResult('competence', 'competence', 'string');
        $rsm->addScalarResult('niveau', 'niveau', 'string');

        /** @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT COUNt(cpt.id) as total, cf.label as competence, l.label as niveau -- , l.index
                FROM participant p
                         INNER JOIN personnage pp ON p.personnage_id = pp.id
                         INNER JOIN personnages_competences pc ON pp.id = pc.personnage_id
                         INNER JOIN competence cpt ON pc.competence_id = cpt.id
                         INNER JOIN competence_family cf ON cpt.competence_family_id = cf.id
                         INNER JOIN `level` l on cpt.level_id = l.id
                WHERE p.gn_id = 7 and pp.vivant = 1
                GROUP BY cpt.id, cf.label, l.index, l.label
                ORDER BY cf.label, l.index, l.label;
                SQL,
            $rsm,
        )->setParameter('gnid', $gn->getId());
    }

    public function getConstructions(): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('fief', 'fief', 'string');
        $rsm->addScalarResult('batiment', 'batiment', 'string');
        $rsm->addScalarResult('description', 'description', 'string');
        $rsm->addScalarResult('defense', 'defense', 'integer');

        /** @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                 SELECT t.nom as fief, c.label as batiment, c.description, c.defense FROM territoire_has_construction hhc
                    INNER JOIN construction c ON hhc.construction_id = c.id
                    INNER JOIN territoire t ON hhc.territoire_id = t.id
                ORDER BY t.nom;
                SQL,
            $rsm,
        );
    }

    public function getConstructionsFiefs(): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('batiment', 'batiment', 'string');
        $rsm->addScalarResult('fiefs', 'fiefs', 'string');
        $rsm->addScalarResult('description', 'description', 'string');

        /** @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                 SELECT c.label as batiment, group_concat(t.nom) as fiefs, c.description FROM territoire_has_construction hhc
                    INNER JOIN construction c ON hhc.construction_id = c.id
                    INNER JOIN territoire t ON hhc.territoire_id = t.id
                GROUP BY t.nom;
                SQL,
            $rsm,
        );
    }
}

<?php

namespace App\Service;

use App\Entity\Gn;
use App\Enum\Role;
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
                GROUP BY c.label;
                SQL,
            $rsm,
        );
    }

    public function getMineurs(Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('nom', 'nom', 'string');
        $rsm->addScalarResult('prenom_usage', 'prenom_usage', 'string');
        $rsm->addScalarResult('prenom', 'prenom', 'string');
        $rsm->addScalarResult('userId', 'userId', 'string');
        $rsm->addScalarResult('email', 'email', 'string');
        $rsm->addScalarResult('email_contact', 'email_contact', 'string');
        $rsm->addScalarResult('groupeId', 'groupeId', 'string');
        $rsm->addScalarResult('groupe', 'groupe', 'string');
        $rsm->addScalarResult('personnageId', 'personnageId', 'string');
        $rsm->addScalarResult('personnage', 'personnage', 'string');
        $rsm->addScalarResult('sensible', 'sensible', 'string');

        /** @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                 SELECT ec.nom,
                   ec.prenom_usage,
                   ec.prenom,
                   u.id as userId,
                   u.email,
                   u.email_contact,
                   g.id      as groupeId,
                   g.nom     as groupe,
                   perso.id  as personnageId,
                   perso.nom as personnage,
                   perso.sensible
                 FROM  participant p
                        INNER JOIN `user` u ON p.user_id = u.id
                        INNER JOIN etat_civil ec ON u.etat_civil_id = ec.id
                        INNER JOIN groupe_gn ggn ON p.groupe_gn_id = ggn.id
                        INNER JOIN groupe g ON ggn.groupe_id = g.id
                        INNER JOIN personnage perso ON p.personnage_id = perso.id
                    WHERE
                    p.gn_id = :gnid
                        AND date_naissance is not null
                      and date_naissance > '0000-00-00 00:00:00'
                      and date_naissance >= DATE_SUB(IF(':gnDate' = '',  ':gnDate' , CURRENT_DATE()), INTERVAL 18 YEAR);
                SQL,
            $rsm,
        )
            ->setParameter('gnid', $gn->getId())
            ->setParameter('gndate', (string) $gn->getDateInstallationJoueur()?->format('Y-m-d'));
    }

    public function getUsersRole(Role $role): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('nom', 'nom', 'string');
        $rsm->addScalarResult('prenom', 'prenom', 'string');
        $rsm->addScalarResult('email', 'email', 'string');
        $rsm->addScalarResult('username', 'username', 'string');

        /** @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                 SELECT u.id, ec.nom, ec.prenom, u.email, u.username
                    FROM `user` u
                             INNER JOIN etat_civil ec ON u.etat_civil_id = ec.id
                    WHERE u.roles LIKE :role
                SQL,
            $rsm,
        )->setParameter('role', '%'.$role->value.'%');
    }
}

<?php

namespace App\Service;

use App\Entity\Classe;
use App\Entity\CompetenceFamily;
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

    public function getAlchimieHerboristeGn(Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('personnageId', 'personnageId', 'integer');
        $rsm->addScalarResult('nom', 'nom', 'string');
        $rsm->addScalarResult('competence', 'competence', 'string');
        $rsm->addScalarResult('niveauMax', 'niveauMax', 'integer');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT pp.id as 'personnageId', pp.nom, cf.label as competence, MAX(l.`index`) as niveauMax -- , l.index
                FROM participant p
                         INNER JOIN personnage pp ON p.personnage_id = pp.id
                         INNER JOIN personnages_competences pc ON pp.id = pc.personnage_id
                         INNER JOIN competence cpt ON pc.competence_id = cpt.id
                         INNER JOIN competence_family cf ON cpt.competence_family_id = cf.id
                         INNER JOIN `level` l on cpt.level_id = l.id
                WHERE p.gn_id = :gnid and ((cf.id = 2 and l.index > 1) or (cf.id = 4 and l.index > 0) )
                GROUP BY pp.id, cf.id, cf.label, l.index
                ORDER BY cf.label, l.index
                SQL,
            $rsm,
        )->setParameter('gnid', $gn->getId());
    }

    public function getBateauxGn(Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('total', 'total', 'integer');
        $rsm->addScalarResult('groupe_id', 'groupe_id', 'integer');
        $rsm->addScalarResult('groupe_gn_id', 'groupe_gn_id', 'integer');
        $rsm->addScalarResult('nom', 'nom', 'string');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT SUM(bateaux) as total, groupe_id, ggn.id as groupe_gn_id, g.nom
                FROM groupe_gn ggn

                         INNER JOIN groupe g ON ggn.groupe_id = g.id
                WHERE ggn.gn_id = :gnid
                GROUP BY groupe_id
                SQL,
            $rsm,
        )->setParameter('gnid', $gn->getId());
    }

    public function getClassesGn(Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('total', 'total', 'integer');
        $rsm->addScalarResult('nom', 'label', 'string');
        $rsm->addScalarResult('id', 'id', 'integer');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT COUNT(c.id) as total, c.label_masculin as nom, c.id
                FROM participant p
                         INNER JOIN personnage pp ON p.personnage_id = pp.id
                         INNER JOIN classe c ON pp.classe_id = c.id
                WHERE p.gn_id = :gnid
                GROUP BY c.id, c.label_masculin
                ORDER BY total DESC, c.label_masculin
                SQL,
            $rsm,
        )->setParameter('gnid', $gn->getId());
    }

    public function getCompetenceFamilyGn(CompetenceFamily $competenceFamily, Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('total', 'total', 'integer');
        $rsm->addScalarResult('label', 'niveau', 'string');
        $rsm->addScalarResult('index', 'indexNiveau', 'integer');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT COUNT(c.id) as total, l.label, l.index
                FROM participant p
                         INNER JOIN personnage pp ON p.personnage_id = pp.id
                         INNER JOIN personnages_competences pc ON pp.id = pc.personnage_id
                         INNER JOIN competence c ON pc.competence_id = c.id
                         INNER JOIN competence_family cf ON c.competence_family_id = cf.id
                         INNER JOIN level l ON c.level_id = l.id
                WHERE p.gn_id = :gnid
                  AND cf.id = :cfid
                GROUP BY c.id,  c.level_id
                SQL,
            $rsm,
        )->setParameter('gnid', $gn->getId())
            ->setParameter('cfid', $competenceFamily->getId());
    }

    public function getCompetenceGn(Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('total', 'total', 'integer');
        $rsm->addScalarResult('competence', 'competence', 'string');
        $rsm->addScalarResult('niveau', 'niveau', 'string');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT COUNt(cpt.id) as total, cf.label as competence, l.label as niveau -- , l.index
                FROM participant p
                         INNER JOIN personnage pp ON p.personnage_id = pp.id
                         INNER JOIN personnages_competences pc ON pp.id = pc.personnage_id
                         INNER JOIN competence cpt ON pc.competence_id = cpt.id
                         INNER JOIN competence_family cf ON cpt.competence_family_id = cf.id
                         INNER JOIN `level` l on cpt.level_id = l.id
                WHERE p.gn_id = :gnid and pp.vivant = 1
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

        /* @noinspection SqlNoDataSourceInspection */
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

        /* @noinspection SqlNoDataSourceInspection */
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

    public function getGameItemWithoutPersonnage(): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('label', 'label', 'string');
        $rsm->addScalarResult('quality_id', 'quality_id', 'integer');
        $rsm->addScalarResult('numero', 'numero', 'integer');
        $rsm->addScalarResult('identification', 'identification', 'integer');
        $rsm->addScalarResult('couleur', 'couleur', 'string');
        $rsm->addScalarResult('quantite', 'quantite', 'integer');
        $rsm->addScalarResult('objet_id', 'objet_id', 'integer');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT i.id, i.label, i.quality_id, i.numero,  i.identification, i.couleur, i.quantite, i.objet_id FROM item i
                LEFT JOIN personnage_has_item phi ON i.id = phi.item_id
                WHERE phi.personnage_id IS NULL
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

        /* @noinspection SqlNoDataSourceInspection */
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
                        LEFT JOIN groupe_gn ggn ON p.groupe_gn_id = ggn.id
                        LEFT JOIN groupe g ON ggn.groupe_id = g.id
                        LEFT JOIN personnage perso ON p.personnage_id = perso.id
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

    public function getPotionsDepartGn(Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('potion_id', 'potion_id', 'integer');
        $rsm->addScalarResult('numero', 'numero', 'integer');
        $rsm->addScalarResult('niveau', 'niveau', 'integer');
        $rsm->addScalarResult('personnage_id', 'personnage_id', 'integer');
        $rsm->addScalarResult('personnage', 'personnage', 'string');
        $rsm->addScalarResult('label', 'label', 'string');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT pot.id as potion_id, p.id as personnage_id, p.nom as personnage, pot.label, pot.numero, pot.niveau
                    FROM participant_potions_depart ppd
                    INNER JOIN potion pot ON ppd.potion_id = pot.id
                    INNER JOIN participant pt ON ppd.participant_id = pt.id
                    INNER JOIN personnage p ON pt.personnage_id = p.id
                WHERE pt.gn_id = :gnid
                SQL,
            $rsm,
        )->setParameter('gnid', $gn->getId());
    }

    public function getReligionsGn(Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('total', 'total', 'integer');
        $rsm->addScalarResult('label', 'label', 'string');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT COUNT(p.id) as total, r.label
                  FROM
                    participant pt
                    INNER JOIN personnage p ON p.id = pt.personnage_id
                    INNER JOIN `personnages_religions` pr ON p.id = pr.personnage_id
                    INNER JOIN religion r ON r.id = pr.religion_id
                WHERE pt.gn_id = :gnid
                GROUP BY r.id,  r.label
                ORDER BY total DESC, r.label
                SQL,
            $rsm,
        )->setParameter('gnid', $gn->getId());
    }

    public function getReligionsLevelGn(Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('total', 'total', 'integer');
        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('label', 'label', 'string');
        $rsm->addScalarResult('level', 'level', 'string');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT COUNT(p.id) as total, r.id, r.label, rl.label as level
                  FROM participant pt
                    INNER JOIN personnage p ON p.id = pt.personnage_id
                    INNER JOIN personnages_religions pr ON p.id = pr.personnage_id
                    INNER JOIN religion r ON r.id = pr.religion_id
                    INNER JOIN religion_level rl ON rl.id = pr.religion_level_id
                WHERE pt.gn_id = :gnid
                GROUP BY r.id,  r.label, level
                ORDER BY total DESC, r.label, level
                SQL,
            $rsm,
        )->setParameter('gnid', $gn->getId());
    }

    public function getRenommeGn(Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('total', 'total', 'integer');
        $rsm->addScalarResult('grp_renomme', 'grp_renomme', 'string');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT COUNT(p.id) as total,
                       CASE
                           WHEN renomme < 6 THEN 'Insufisante'
                           WHEN renomme < 11 THEN 'Etoile noir'
                           WHEN renomme < 21 THEN 'Etoile dorée'
                           ELSE 'Grande broche'
                           END     as grp_renomme
                FROM participant p
                         INNER JOIN personnage pp ON p.personnage_id = pp.id
                WHERE p.gn_id = :gnid
                  and renomme is not null
                  and renomme > 0
                GROUP BY grp_renomme
                SQL,
            $rsm,
        )->setParameter('gnid', $gn->getId());
    }

    public function getUsersRole(Role $role): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('nom', 'nom', 'string');
        $rsm->addScalarResult('prenom', 'prenom', 'string');
        $rsm->addScalarResult('email', 'email', 'string');
        $rsm->addScalarResult('username', 'username', 'string');

        /* @noinspection SqlNoDataSourceInspection */
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

    public function getXpGap(Gn $gn, int $gap = 50): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('personnage_id', 'personnage_id', 'integer');
        $rsm->addScalarResult('personnage_nom', 'personnage_nom', 'string');
        $rsm->addScalarResult('xp_restant', 'xp_restant', 'integer');
        $rsm->addScalarResult('total', 'total', 'integer');
        $rsm->addScalarResult('groupe_id', 'groupe_id', 'integer');
        $rsm->addScalarResult('groupe_nom', 'groupe_nom', 'string');
        $rsm->addScalarResult('groupe_gn_id', 'groupe_gn_id', 'string');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                 SELECT DISTINCT perso.id as personnage_id, perso.nom as personnage_nom, xp as xp_restant, sum(xp_gain) as total, g.id as groupe_id, g.nom as groupe_nom
                FROM participant p
                         INNER JOIN personnage perso ON p.personnage_id = perso.id
                INNER JOIN experience_gain eg ON perso.id = eg.personnage_id
                INNER JOIN groupe_gn ggn ON p.groupe_gn_id = ggn.id
                INNER JOIN groupe g ON ggn.groupe_id = g.id
                WHERE p.gn_id = 7 and explanation NOT LIKE 'Suppression de la compétence %'
                GROUP BY perso.id, perso.nom
                HAVING sum(xp_gain) >= 50
                ORDER BY total DESC
            SQL,
            $rsm,
        )
            ->setParameter('gnid', $gn->getId())
            ->setParameter('gap', $gap);
    }

    public function nbClasseGroupeGn(Gn $gn, Classe $classe): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('total', 'total', 'integer');
        $rsm->addScalarResult('groupe_name', 'groupe_name', 'string');
        $rsm->addScalarResult('groupe_id', 'groupe_id', 'integer');
        $rsm->addScalarResult('groupe_gn_id', 'groupe_gn_id', 'integer');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT COUNT(DISTINCT pt.personnage_id) as total, g.nom as groupe_name, g.id as groupe_id, pt.groupe_gn_id
                FROM participant pt
                         INNER JOIN personnage p ON pt.personnage_id = p.id
                         INNER JOIN groupe_gn ggn ON pt.groupe_gn_id = ggn.id
                         INNER JOIN groupe g ON ggn.groupe_id = g.id
                WHERE pt.gn_id = 7
                  AND classe_id = :classe_id
                GROUP BY g.id
                ORDER BY total DESC
                SQL,
            $rsm,
        )
            ->setParameter('gnid', $gn->getId())
            ->setParameter('classe_id', $classe->getId());
    }

    public function renommeGroupeGn(Gn $gn, ?int $equalOrUpper = null): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('total', 'total', 'integer');
        $rsm->addScalarResult('groupe_name', 'groupe_name', 'string');
        $rsm->addScalarResult('groupe_id', 'groupe_id', 'integer');
        $rsm->addScalarResult('groupe_gn_id', 'groupe_gn_id', 'integer');

        $equalOrUpperParam = '';
        if (is_int($equalOrUpper)) {
            $equalOrUpperParam = 'AND renomme >= '.$equalOrUpper;
        }

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT COUNT(DISTINCT pt.personnage_id) as total, g.nom as groupe_name, g.id as groupe_id, pt.groupe_gn_id
                FROM participant pt
                         INNER JOIN personnage p ON pt.personnage_id = p.id
                         INNER JOIN groupe_gn ggn ON pt.groupe_gn_id = ggn.id
                         INNER JOIN groupe g ON ggn.groupe_id = g.id
                WHERE pt.gn_id = 7
                  $equalOrUpperParam
                GROUP BY g.id
                ORDER BY total DESC
                SQL,
            $rsm,
        )->setParameter('gnid', $gn->getId());
    }

    public function sensibleGn(Gn $gn): NativeQuery
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

        /* @noinspection SqlNoDataSourceInspection */
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
                   perso.nom as personnage
                 FROM  participant p
                        INNER JOIN `user` u ON p.user_id = u.id
                        INNER JOIN etat_civil ec ON u.etat_civil_id = ec.id
                        INNER JOIN groupe_gn ggn ON p.groupe_gn_id = ggn.id
                        INNER JOIN groupe g ON ggn.groupe_id = g.id
                        INNER JOIN personnage perso ON p.personnage_id = perso.id
                    WHERE
                    p.gn_id = :gnid
                        AND sensible = 1;
                SQL,
            $rsm,
        )
            ->setParameter('gnid', $gn->getId());
    }
}

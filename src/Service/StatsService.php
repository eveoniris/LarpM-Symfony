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
    )
    {
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
        $rsm->addScalarResult('groupe_id', 'groupe_id', 'integer');
        $rsm->addScalarResult('groupe_numero', 'groupe_numero', 'integer');
        $rsm->addScalarResult('groupe_gn_id', 'groupe_gn_id', 'integer');
        $rsm->addScalarResult('groupe_nom', 'groupe_nom', 'string');
        $rsm->addScalarResult('bateaux', 'bateaux', 'integer');
        $rsm->addScalarResult('emplacement', 'emplacement', 'string');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT groupe_id,  g.numero as groupe_numero, ggn.id as groupe_gn_id, g.nom as groupe_nom, bateaux, bateaux_localisation as emplacement
                FROM groupe_gn ggn
                INNER JOIN groupe g ON ggn.groupe_id = g.id
                WHERE ggn.gn_id = :gnid
                SQL,
            $rsm,
        )->setParameter('gnid', $gn->getId());
    }

    public function getBateauxOrdreGn(Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('groupe_numero', 'groupe_numero', 'integer');
        $rsm->addScalarResult('groupe_nom', 'groupe_nom', 'string');
        $rsm->addScalarResult('bateaux', 'bateaux', 'integer');
        $rsm->addScalarResult('emplacement', 'emplacement', 'string');
        $rsm->addScalarResult('suzerain', 'suzerain', 'string');
        $rsm->addScalarResult('navigateur', 'navigateur', 'string');
        $rsm->addScalarResult('initiative', 'initiative', 'integer');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT g.numero             as groupe_numero,
                       g.nom                as groupe_nom,
                       bateaux,
                       bateaux_localisation as emplacement,
                       suzerain.nom as suzerain,
                       navigateur.nom as navigateur,
                       ggn.initiative
                FROM groupe_gn ggn
                         INNER JOIN groupe g ON ggn.groupe_id = g.id
                         LEFT JOIN personnage as suzerain ON ggn.suzerin_id = suzerain.id
                         LEFT JOIN personnage as navigateur ON ggn.navigateur_id = navigateur.id
                WHERE ggn.gn_id = :gnid and bateaux > 0
                ORDER BY g.numero ASC
                SQL,
            $rsm,
        )->setParameter('gnid', $gn->getId());
    }

    public function getWhosWho(Gn $gn, int $renomme = 20): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('renomme', 'renomme', 'integer');
        $rsm->addScalarResult('personnage_id', 'personnage_id', 'integer');
        $rsm->addScalarResult('personnage_nom', 'personnage_nom', 'string');
        $rsm->addScalarResult('personnage_trombine_url', 'personnage_trombine_url', 'string');
        $rsm->addScalarResult('groupe_id', 'groupe_id', 'integer');
        $rsm->addScalarResult('groupe_nom', 'groupe_nom', 'string');
        $rsm->addScalarResult('user_id', 'user_id', 'integer');
        $rsm->addScalarResult('user_prenom', 'user_prenom', 'string');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT p.renomme, p.id as personnage_id, p.nom personnage_nom, p.trombineUrl as personnage_trombine_url, g.id as groupe_id, g.nom as groupe_nom, u.id as user_id, ec.prenom as user_prenom
                FROM `personnage` p
                INNER JOIN participant pt ON pt.personnage_id = p.id
                INNER JOIN groupe_gn ggn ON ggn.id = pt.groupe_gn_id
                INNER JOIN groupe g ON ggn.groupe_id = g.id
                INNER JOIN `user` u ON u.id = pt.user_id
                INNER JOIN etat_civil ec ON u.etat_civil_id = ec.id
                WHERE p.renomme >= :renomme and pt.gn_id = :gnid and p.vivant = 1
                GROUP BY p.id
                SQL,
            $rsm,
        )->setParameter('gnid', $gn->getId())->setParameter(':renomme', $renomme);
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

    public function getFiefsState(): NativeQuery
    {
        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('fief', 'fief', 'string');
        $rsm->addScalarResult('groupe_id', 'groupe_id', 'integer');
        $rsm->addScalarResult('groupe', 'groupe', 'string');
        $rsm->addScalarResult('religion', 'religion', 'string');
        $rsm->addScalarResult('defense', 'defense', 'integer');
        $rsm->addScalarResult('stable', 'stable', 'string');
        $rsm->addScalarResult('instable', 'instable', 'string');
        $rsm->addScalarResult('revenus', 'revenus', 'string');
        $rsm->addScalarResult('murailles', 'murailles', 'string');
        $rsm->addScalarResult('bastion', 'bastion', 'string');
        $rsm->addScalarResult('forteresse', 'forteresse', 'string');
        $rsm->addScalarResult('temple', 'temple', 'string');
        $rsm->addScalarResult('sanctuaire', 'sanctuaire', 'string');
        $rsm->addScalarResult('comptoir', 'comptoir', 'string');
        $rsm->addScalarResult('merveille', 'merveille', 'string');
        $rsm->addScalarResult('palais', 'palais', 'string');
        $rsm->addScalarResult('route', 'route', 'string');
        $rsm->addScalarResult('port', 'port', 'string');
        $rsm->addScalarResult('total_defense', 'total_defense', 'string');
        $rsm->addScalarResult('total_revenus', 'total_revenus', 'string');
        $rsm->addScalarResult('suzerain', 'suzerain', 'string');
        $rsm->addScalarResult('renommee', 'renommee', 'integer');
        $rsm->addScalarResult('technologies', 'technologies', 'string');
        $rsm->addScalarResult('exportations', 'exportations', 'string');
        $rsm->addScalarResult('ingredients', 'ingredients', 'string');
        $rsm->addScalarResult('ressource', 'ingredients', 'string');
        $rsm->addScalarResult('@export', '@export', 'string');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                 SELECT
                    a.id as id
                    , a.nom as fief
                    , b.numero as groupe_id
                    , b.nom as groupe
                    , m.label as religion
                    , case a.resistance
                        when NULL then 0
                        else a.resistance
                        end as defense
                    , case a.statut
                        when LOWER('instable') then ''
                        else 'X'
                        end as stable
                    , case LOWER(a.statut)
                        when 'instable' then 'X'
                        else ''
                        end as instable
                    , a.tresor as revenus
                    , case c.construction_id
                        when 1 then 'X'
                        else ''
                        end as murailles
                    , case d.construction_id
                        when 2 then 'X'
                        else ''
                        end as bastion
                    , case e.construction_id
                        when 3 then 'X'
                        else ''
                        end as forteresse
                    , case f.construction_id
                        when 4 then 'X'
                        else ''
                        end as temple
                    , case g.construction_id
                        when 5 then 'X'
                        else ''
                        end as sanctuaire
                    , case h.construction_id
                        when 6 then 'X'
                        else ''
                        end as comptoir
                    , case i.construction_id
                        when 7 then 'X'
                        else ''
                        end as merveille
                    , case j.construction_id
                        when 8 then 'X'
                        else ''
                        end as palais
                    , case k.construction_id
                        when 9 then 'X'
                        else ''
                        end as route
                    , case l.construction_id
                        when 10 then 'X'
                        else ''
                        end as port
                    , case  LOWER(a.statut)
                        when 'instable' then CEILING((a.resistance + case c.construction_id when 1 then 2 else 0 end + case e.construction_id when 3 then 2 else 0 end) / 2)
                        else a.resistance + case c.construction_id when 1 then 2 else 0 end + case e.construction_id when 3 then 2 else 0 end
                      end as total_defense
                    , case LOWER(a.statut)
                        when 'instable' then CEILING((a.tresor + case h.construction_id	when 6 then 5 else 0 end + case l.construction_id when 10 then 5 else 0 end) / 2)
                        else a.tresor + case h.construction_id	when 6 then 5 else 0 end + case l.construction_id when 10 then 5 else 0 end
                      end as total_revenus
                    , CASE WHEN (suzerain.nom <> '' AND suzerain.nom IS NOT NULL)
                        THEN suzerain.nom
                        ELSE
                            CASE WHEN (groupe_leader.nom <> '' AND groupe_leader.nom IS NOT NULL)
                            THEN groupe_leader.nom
                            ELSE a.dirigeant
                            END
                        END as suzerain
                    , suzerain.renomme as renommee
                    , GROUP_CONCAT( DISTINCT q.label ORDER BY q.label ) as technologies
                    , GROUP_CONCAT( DISTINCT t.label ORDER BY t.label ) as exportations
                    , GROUP_CONCAT( DISTINCT v.label ORDER BY v.label ) as ingredients
                    , p.label as ressource
                     , CONCAT("..\\\Links\\\",min(p.label),".ai") as '@export'
                FROM `territoire` as a
                left join groupe as b on b.id = a.groupe_id
                left join territoire_has_construction as c on c.territoire_id = a.id and c.construction_id = 1
                left join territoire_has_construction as d on d.territoire_id = a.id and d.construction_id = 2
                left join territoire_has_construction as e on e.territoire_id = a.id and e.construction_id = 3
                left join territoire_has_construction as f on f.territoire_id = a.id and f.construction_id = 4
                left join territoire_has_construction as g on g.territoire_id = a.id and g.construction_id = 5
                left join territoire_has_construction as h on h.territoire_id = a.id and h.construction_id = 6
                left join territoire_has_construction as i on i.territoire_id = a.id and i.construction_id = 7
                left join territoire_has_construction as j on j.territoire_id = a.id and j.construction_id = 8
                left join territoire_has_construction as k on k.territoire_id = a.id and k.construction_id = 9
                left join territoire_has_construction as l on l.territoire_id = a.id and l.construction_id = 10
                left join religion as m on m.id = a.religion_id
                left join territoire_importation as n on n.territoire_id = a.id
                left join ressource as p on p.id = n.ressource_id
                left join territoire_has_construction as r on r.territoire_id = a.id and r.construction_id > 10
                left join construction as q on q.id = r.construction_id
                left join territoire_exportation as s on s.territoire_id = a.id
                left join ressource as t on t.id = s.ressource_id
                left join territoire_ingredient as u on u.territoire_id = a.id
                left join ingredient as v on v.id = u.ingredient_id
                left join groupe_gn ggn ON ggn.id = (SELECT ggn2.id FROM groupe_gn ggn2 WHERE b.id = ggn2.groupe_id ORDER BY ggn2.id DESC LIMIT 1)
                left join personnage as suzerain ON ggn.suzerin_id = suzerain.id
                left join personnage as groupe_leader ON ggn.responsable_id = groupe_leader.id
                WHERE a.territoire_id is not null
                  and a.tresor is not null
                group by 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23
                order by b.nom, a.id;
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
            ->setParameter('gndate', (string)$gn->getDateInstallationJoueur()?->format('Y-m-d'));
    }

    public function getListeArrivee(Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('nom', 'nom', 'string');
        $rsm->addScalarResult('prenom', 'prenom', 'string');
        $rsm->addScalarResult('groupe', 'groupe', 'string');
        $rsm->addScalarResult('couchage', 'couchage', 'string');
        $rsm->addScalarResult('special', 'special', 'string');

        /* @noinspection SqlNoDataSourceInspection */
        return $this->entityManager->createNativeQuery(
            <<<SQL
                 SELECT ec.nom, ec.prenom, concat(g.numero, ' - ', g.nom) as groupe,
                    IF(pt.couchage = 'HSJ', 'Hors site', pt.couchage),
                        CASE
                            WHEN pt.couchage = 'HSJ' THEN "Hors site de jeu"
                            WHEN pt.couchage = 'HRP' THEN "Camps HRP"
                            WHEN pt.couchage = 'RP' THEN "Sur son camps"
                            ELSE ""
                        END as couchage
                        , pt.special
                    FROM `personnage` p
                             INNER JOIN participant pt ON pt.personnage_id = p.id
                             INNER JOIN groupe_gn ggn ON ggn.id = pt.groupe_gn_id
                             INNER JOIN groupe g ON ggn.groupe_id = g.id
                             INNER JOIN `user` u ON u.id = pt.user_id
                             INNER JOIN etat_civil ec ON u.etat_civil_id = ec.id
                    WHERE pt.gn_id = :gnid
                    ORDER BY nom, prenom, numero;
                SQL,
            $rsm,
        )
            ->setParameter('gnid', $gn->getId());
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
        )->setParameter('role', '%' . $role->value . '%');
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
            $equalOrUpperParam = 'AND renomme >= ' . $equalOrUpper;
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

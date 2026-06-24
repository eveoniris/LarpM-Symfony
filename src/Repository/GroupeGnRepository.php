<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\Personnage;
use App\Entity\User;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\ResultSetMapping;

class GroupeGnRepository extends BaseRepository
{
    public function countTitres(Personnage $personnage, ?Gn $gn = null, ?GroupeGn $excludeGroupeGn = null): int
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('titre', 'titre', 'string');

        $pid = $personnage->getId();
        $sql = <<<SQL
            SELECT 
                SUM(
                    IF(suzerin_id = {$pid}, 1, 0) + 
                    IF(connetable_id = {$pid}, 1, 0) + 
                    IF(intendant_id = {$pid}, 1, 0) + 
                    IF(navigateur_id = {$pid}, 1, 0) + 
                    IF(camarilla_id = {$pid}, 1, 0) + 
                    IF(diplomate_id = {$pid}, 1, 0) 
                    ) AS total
            FROM groupe_gn as ggn  
            INNER JOIN groupe as g ON g.id = ggn.groupe_id
            WHERE (suzerin_id = :pid 
                OR connetable_id = :pid 
                OR intendant_id = :pid 
                OR navigateur_id = :pid 
                OR camarilla_id = :pid
                OR diplomate_id = :pid)
            SQL;

        if ($gn) {
            $sql .= ' AND ggn.gn_id = :gnid';
        }

        if ($excludeGroupeGn) {
            $sql .= ' AND ggn.id <> :ggnid';
        }

        $sql .= ' ORDER BY ggn.gn_id DESC LIMIT 1';
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        if ($gn) {
            $query->setParameter('gnid', $gn->getId());
        }
        if ($excludeGroupeGn) {
            $query->setParameter('ggnid', $excludeGroupeGn->getId());
        }

        try {
            return (int) $query->setParameter('pid', $personnage->getId())->getSingleScalarResult() ?: 0;
        } catch (NoResultException $e) {
            return 0;
        }
    }

    /** @return list<GroupeGn> */
    public function findByGn(int $gnId): array
    {
        return $this->getEntityManager()->createQuery('SELECT g FROM App\Entity\GroupeGn g JOIN g.gn gn WHERE gn.id = :gnId')->setParameter('gnId', $gnId)->getResult();
    }

    /**
     * Trouve un groupe en fonction de son code.
     *
     * @param string $code
     */
    public function findOneByCode($code): mixed
    {
        $groupeGns = $this->getEntityManager()->createQuery('SELECT g FROM App\Entity\GroupeGn g WHERE g.code = :code')->setParameter('code', $code)->getResult();

        return reset($groupeGns);
    }

    public function getTitres(Personnage $personnage, ?Gn $gn = null, ?GroupeGn $excludeGroupeGn = null): string
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('titre', 'titre', 'string');

        $pid = $personnage->getId();
        // Attention le concat ne prendra qu'un titre car un pj ne peut en avoir qu'un
        $sql = <<<SQL
            SELECT 
                CONCAT(
                   case 
                       when suzerin_id = {$pid} then 'Suzerain'
                       when connetable_id = {$pid} then 'Chef de guerre'
                       when intendant_id = {$pid} then 'Intendant'
                       when navigateur_id = {$pid} then 'Navigateur'
                       when camarilla_id  = {$pid} then 'Eminence grise'
                       when diplomate_id  = {$pid} then 'Diplomate'
                   end,
                   ' - ',
                   g.numero,
                   ' - ',
                   g.nom
                   ) as titre
            FROM groupe_gn as ggn  
            INNER JOIN groupe as g ON g.id = ggn.groupe_id
            WHERE (suzerin_id = :pid 
                OR connetable_id = :pid 
                OR intendant_id = :pid 
                OR navigateur_id = :pid 
                OR camarilla_id = :pid
                OR diplomate_id = :pid)
            SQL;

        if ($gn) {
            $sql .= ' AND ggn.gn_id = :gnid';
        }

        if ($excludeGroupeGn) {
            $sql .= ' AND ggn.id <> :ggnid';
        }

        $sql .= ' ORDER BY ggn.gn_id DESC LIMIT 1';
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        if ($gn) {
            $query->setParameter('gnid', $gn->getId());
        }
        if ($excludeGroupeGn) {
            $query->setParameter('ggnid', $excludeGroupeGn->getId());
        }

        try {
            return (string) ($query->setParameter('pid', $personnage->getId())->getSingleScalarResult() ?? '');
        } catch (NoResultException $e) {
            return '';
        }
    }

    /**
     * Retourne les emails des personnages ayant un rôle donné dans les groupes du GN.
     * $roleColumn doit être une colonne DB valide de groupe_gn (usage interne uniquement).
     */
    public function getEmailsByRole(Gn $gn, string $roleColumn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('prenom', 'prenom', 'string');
        $rsm->addScalarResult('nom', 'nom', 'string');
        $rsm->addScalarResult('email', 'email', 'string');
        $rsm->addScalarResult('groupe_nom', 'groupe_nom', 'string');

        return $this
            ->getEntityManager()
            ->createNativeQuery(<<<SQL
                SELECT ec.prenom, ec.nom, u.email, g.nom AS groupe_nom
                FROM groupe_gn gg
                JOIN groupe g ON g.id = gg.groupe_id
                JOIN personnage per ON per.id = gg.{$roleColumn}
                JOIN `user` u ON u.id = per.user_id
                LEFT JOIN etat_civil ec ON ec.id = u.etat_civil_id
                WHERE gg.gn_id = :gnId
                ORDER BY ec.nom, ec.prenom
                SQL, $rsm)
            ->setParameter('gnId', $gn->getId());
    }

    /**
     * Retourne les emails des suzerains du GN.
     * Si aucun suzerain n'est défini pour un groupe, utilise le responsable administratif.
     */
    public function getEmailsSuzerains(Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('prenom', 'prenom', 'string');
        $rsm->addScalarResult('nom', 'nom', 'string');
        $rsm->addScalarResult('email', 'email', 'string');
        $rsm->addScalarResult('groupe_nom', 'groupe_nom', 'string');

        return $this
            ->getEntityManager()
            ->createNativeQuery(<<<SQL
                SELECT
                  COALESCE(suz_ec.prenom, resp_ec.prenom) AS prenom,
                  COALESCE(suz_ec.nom, resp_ec.nom) AS nom,
                  COALESCE(suz_u.email, resp_u.email) AS email,
                  g.nom AS groupe_nom
                FROM groupe_gn gg
                JOIN groupe g ON g.id = gg.groupe_id
                LEFT JOIN personnage suz_per ON suz_per.id = gg.suzerin_id
                LEFT JOIN `user` suz_u ON suz_u.id = suz_per.user_id
                LEFT JOIN etat_civil suz_ec ON suz_ec.id = suz_u.etat_civil_id
                LEFT JOIN participant resp ON resp.id = gg.responsable_id
                LEFT JOIN `user` resp_u ON resp_u.id = resp.user_id
                LEFT JOIN etat_civil resp_ec ON resp_ec.id = resp_u.etat_civil_id
                WHERE gg.gn_id = :gnId
                  AND (gg.suzerin_id IS NOT NULL OR gg.responsable_id IS NOT NULL)
                ORDER BY COALESCE(suz_ec.nom, resp_ec.nom), COALESCE(suz_ec.prenom, resp_ec.prenom)
                SQL, $rsm)
            ->setParameter('gnId', $gn->getId());
    }

    /**
     * Retourne les emails des responsables administratifs de chaque groupe du GN.
     */
    public function getEmailsResponsables(Gn $gn): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('prenom', 'prenom', 'string');
        $rsm->addScalarResult('nom', 'nom', 'string');
        $rsm->addScalarResult('email', 'email', 'string');
        $rsm->addScalarResult('groupe_nom', 'groupe_nom', 'string');

        return $this
            ->getEntityManager()
            ->createNativeQuery(<<<SQL
                SELECT ec.prenom, ec.nom, u.email, g.nom AS groupe_nom
                FROM groupe_gn gg
                JOIN groupe g ON g.id = gg.groupe_id
                JOIN participant resp ON resp.id = gg.responsable_id
                JOIN `user` u ON u.id = resp.user_id
                LEFT JOIN etat_civil ec ON ec.id = u.etat_civil_id
                WHERE gg.gn_id = :gnId
                ORDER BY ec.nom, ec.prenom
                SQL, $rsm)
            ->setParameter('gnId', $gn->getId());
    }

    public function userIsMemberOfGroupe(User $user, GroupeGn $groupeGn): bool
    {
        $query = $this->getEntityManager()->createQuery(<<<DQL
            SELECT MAX(grp.id) as exists
            FROM App\Entity\User u 
            INNER JOIN u.participants as part
            INNER JOIN part.groupeGn as grp
            WHERE u.id = :uid AND grp.id = :gid
            DQL);

        return (bool) $query->setParameter('uid', $user->getId())->setParameter('gid', $groupeGn->getId())->getSingleScalarResult();
    }
}

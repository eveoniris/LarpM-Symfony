<?php

namespace App\Repository;

use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\Personnage;
use App\Entity\User;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Security\Core\User\UserInterface;

class GroupeGnRepository extends BaseRepository
{
    public function findByGn($gnId)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\GroupeGn g JOIN g.gn gn WHERE gn.id = :gnId')
            ->setParameter('gnId', $gnId)
            ->getResult();
    }

    /**
     * Trouve un groupe en fonction de son code.
     *
     * @param string $code
     *
     * @return App\Entity\GroupeGn $groupeGn
     */
    public function findOneByCode($code)
    {
        $groupeGns = $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\GroupeGn g WHERE g.code = :code')
            ->setParameter('code', $code)
            ->getResult();

        return reset($groupeGns);
    }

    public function getTitres(Personnage $personnage, ?Gn $gn = null, ?GroupeGn $excludeGroupeGn = null): string
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('titre', 'titre', 'string');

        $pid = $personnage->getId();
        $sql = <<<SQL
                SELECT 
                    CONCAT(
                       case 
                           when suzerin_id = $pid then 'Suzerin'
                           when connetable_id = $pid then 'Chef de guerre'
                           when intendant_id = $pid then 'Intendant'
                           when navigateur_id = $pid then 'Navigateur'
                           when camarilla_id  = $pid then 'Eminence grise'
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
                    OR camarilla_id = :pid)
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
            return $query
                ->setParameter('pid', $personnage->getId())
                ->getSingleScalarResult() ?: '';
        } catch (NoResultException $e) {
            return '';
        }
    }

    public function userIsMemberOfGroupe(UserInterface|User $user, GroupeGn $groupeGn): bool
    {
        $query = $this->getEntityManager()
            ->createQuery(
                <<<DQL
                SELECT MAX(grp.id) as exists
                FROM App\Entity\User u 
                INNER JOIN u.participants as part
                INNER JOIN part.groupeGn as grp
                WHERE u.id = :uid AND grp.id = :gid
                DQL,
            );

        return (bool)$query
            ->setParameter('uid', $user->getId())
            ->setParameter('gid', $groupeGn->getId())
            ->getSingleScalarResult();
    }
}

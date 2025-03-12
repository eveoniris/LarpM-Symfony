<?php

namespace App\Repository;

use App\Entity\GroupeGn;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class GroupeGnRepository extends BaseRepository
{
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

    public function findByGn($gnId)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\GroupeGn g JOIN g.gn gn WHERE gn.id = :gnId')
            ->setParameter('gnId', $gnId)
            ->getResult();
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
                DQL
            );

        return (bool) $query
            ->setParameter('uid', $user->getId())
            ->setParameter('gid', $groupeGn->getId())
            ->getSingleScalarResult();
    }
}
